import os
import re
import shutil
import logging
import subprocess
from typing import Dict, List, Optional, Any
import frida

logger = logging.getLogger("erm.frida.device_manager")

class ActiveSession:
    def __init__(self, session_id: str, device_id: str, target: str, mode: str, session: Any):
        self.session_id = session_id
        self.device_id = device_id
        self.target = target
        self.mode = mode
        self.session = session
        self.scripts: List[Any] = []
        self.outputs: List[Dict[str, Any]] = []
        self.is_active = True

    def append_output(self, message: Dict[str, Any], data: Any = None):
        entry = {
            "type": message.get("type", "log"),
            "payload": message.get("payload", message),
            "has_data": data is not None
        }
        self.outputs.append(entry)

    def stop(self):
        self.is_active = False
        try:
            for s in self.scripts:
                try:
                    s.unload()
                except Exception:
                    pass
            if self.session:
                self.session.detach()
        except Exception as e:
            logger.warning(f"Error detaching session {self.session_id}: {e}")


class FridaDeviceManager:
    def __init__(self):
        self.sessions: Dict[str, ActiveSession] = {}
        self.remote_hosts: List[str] = []
        self.adb_bin = shutil.which("adb")
        self.frida_server_bin = os.getenv("FRIDA_SERVER_BINARY", "/opt/frida/frida-server")
        self.frida_server_path = "/data/local/tmp/frida-server"

    def is_adb_available(self) -> bool:
        return shutil.which("adb") is not None

    def get_frida_version(self) -> str:
        return getattr(frida, "__version__", "unknown")

    def enumerate_devices(self) -> List[Dict[str, Any]]:
        devices_info = []
        try:
            device_manager = frida.get_device_manager()
            for dev in device_manager.enumerate_devices():
                devices_info.append({
                    "id": dev.id,
                    "name": dev.name,
                    "type": str(dev.type),
                })
        except Exception as e:
            logger.warning(f"Failed to enumerate Frida devices: {e}")
        
        return devices_info

    def get_device(self, device_id: Optional[str] = None) -> Any:
        device_manager = frida.get_device_manager()
        
        if device_id:
            try:
                return device_manager.get_device(device_id)
            except Exception:
                pass
            
            # Check if matching by type or name
            for dev in device_manager.enumerate_devices():
                if dev.id == device_id or dev.name == device_id:
                    return dev
            
            raise ValueError(f"Device with ID or name '{device_id}' not found.")
        
        # Default: Try USB device first, then remote, then local
        try:
            return frida.get_usb_device(timeout=2)
        except Exception:
            pass

        try:
            return frida.get_remote_device()
        except Exception:
            pass

        return frida.get_local_device()

    def _validate_serial(self, serial: str) -> None:
        if not re.fullmatch(r"[A-Za-z0-9._:-]{1,128}", serial):
            raise ValueError("Invalid ADB device serial.")

    def list_adb_devices(self) -> List[Dict[str, str]]:
        if not self.is_adb_available():
            raise RuntimeError("ADB is not installed in the tool container.")
        try:
            result = subprocess.run(
                [self.adb_bin, "devices", "-l"], capture_output=True, text=True,
                timeout=15, check=False,
            )
        except subprocess.TimeoutExpired as exc:
            raise RuntimeError("ADB device listing timed out.") from exc
        if result.returncode != 0:
            raise RuntimeError("ADB device listing failed.")
        devices: List[Dict[str, str]] = []
        for line in result.stdout.splitlines()[1:]:
            fields = line.strip().split(maxsplit=1)
            if len(fields) >= 2 and fields[1].split()[0] in {"device", "offline", "unauthorized"}:
                devices.append({"serial": fields[0], "state": fields[1].split()[0], "details": fields[1]})
        return devices

    def _resolve_adb_serial(self, serial: Optional[str]) -> str:
        devices = [d for d in self.list_adb_devices() if d["state"] == "device"]
        if serial:
            self._validate_serial(serial)
            if any(d["serial"] == serial for d in devices):
                return serial
            raise ValueError("Requested device is not connected and authorized.")
        if len(devices) != 1:
            if not devices:
                raise ValueError("No authorized Android device is connected.")
            raise ValueError("Multiple devices are connected; specify device_serial.")
        return devices[0]["serial"]

    def _adb(self, args: List[str], timeout: int = 20) -> subprocess.CompletedProcess:
        if not self.is_adb_available():
            raise RuntimeError("ADB is not installed in the tool container.")
        try:
            return subprocess.run(
                [self.adb_bin, *args], capture_output=True, text=True,
                timeout=timeout, check=False,
            )
        except subprocess.TimeoutExpired as exc:
            raise RuntimeError("ADB command timed out.") from exc

    def frida_server_status(self, serial: Optional[str] = None) -> Dict[str, Any]:
        device = self._resolve_adb_serial(serial)
        result = self._adb(["-s", device, "shell", "su", "-c", "pidof frida-server"])
        pid = result.stdout.strip()
        return {
            "success": True,
            "serial": device,
            "running": result.returncode == 0 and bool(pid),
            "pid": pid or None,
        }

    def start_frida_server(self, serial: Optional[str] = None) -> Dict[str, Any]:
        device = self._resolve_adb_serial(serial)
        if not os.path.isfile(self.frida_server_bin) or not os.access(self.frida_server_bin, os.R_OK):
            raise RuntimeError("Configured frida-server binary is not readable in the tool container.")

        push = self._adb(["-s", device, "push", self.frida_server_bin, self.frida_server_path], timeout=120)
        if push.returncode != 0:
            logger.error("frida-server push failed for device %s", device)
            raise RuntimeError("Could not push frida-server to the Android device.")

        chmod = self._adb(["-s", device, "shell", "su", "-c", "chmod 755 " + self.frida_server_path])
        if chmod.returncode != 0:
            raise RuntimeError("Could not set frida-server executable permissions; check device root access.")

        start = self._adb([
            "-s", device, "shell", "su", "-c",
            "nohup " + self.frida_server_path + " >/data/local/tmp/frida-server.log 2>&1 </dev/null &",
        ])
        if start.returncode != 0:
            raise RuntimeError("frida-server start failed; verify the binary ABI and root access.")

        status = self.frida_server_status(device)
        if not status["running"]:
            raise RuntimeError("frida-server did not become available after starting.")
        return {"success": True, "serial": device, "pid": status["pid"], "message": "Frida server started."}

    def stop_frida_server(self, serial: Optional[str] = None) -> Dict[str, Any]:
        device = self._resolve_adb_serial(serial)
        stopped = self._adb([
            "-s", device, "shell", "su", "-c",
            "pkill -x frida-server || killall frida-server || true",
        ])
        if stopped.returncode != 0:
            raise RuntimeError("Could not stop frida-server; check root access.")
        status = self.frida_server_status(device)
        if status["running"]:
            raise RuntimeError("frida-server is still running.")
        return {"success": True, "serial": device, "message": "Frida server stopped."}

    def connect_remote_adb(self, host: str, port: int = 5555) -> Dict[str, Any]:
        # Syntactic validation plus deployment allow-list prevents an authenticated caller
        # from using the tool service as an unrestricted network pivot.
        if not re.fullmatch(r"[a-zA-Z0-9.\-_]{1,253}", host):
            raise ValueError("Invalid hostname or IP address format.")
        allowed_hosts = {item.strip().lower() for item in os.getenv("ADB_ALLOWED_HOSTS", "").split(",") if item.strip()}
        if host.lower() not in allowed_hosts:
            raise ValueError("ADB target is not in the configured allow-list.")
        
        if not (1 <= port <= 65535):
            raise ValueError("Port must be between 1 and 65535.")

        target = f"{host}:{port}"
        
        # 1. Run ADB connect
        adb_output = "ADB not installed"
        if self.is_adb_available():
            try:
                res = subprocess.run(
                    ["adb", "connect", target],
                    capture_output=True,
                    text=True,
                    timeout=10
                )
                adb_output = res.stdout.strip()
            except Exception as e:
                adb_output = f"ADB connect error: {str(e)}"

        # 2. Add remote device to Frida device manager
        frida_status = "unregistered"
        try:
            frida_mgr = frida.get_device_manager()
            frida_mgr.add_remote_device(target)
            if target not in self.remote_hosts:
                self.remote_hosts.append(target)
            frida_status = "registered"
        except Exception as e:
            frida_status = f"Frida remote error: {str(e)}"

        return {
            "target": target,
            "adb_response": adb_output,
            "frida_status": frida_status
        }

    def list_applications(self, device_id: Optional[str] = None) -> List[Dict[str, Any]]:
        device = self.get_device(device_id)
        apps = []
        try:
            for app in device.enumerate_applications():
                apps.append({
                    "identifier": app.identifier,
                    "name": app.name,
                    "pid": getattr(app, "pid", 0)
                })
        except Exception as e:
            logger.error(f"Failed to enumerate applications on device '{device_id}': {e}")
            raise RuntimeError(f"Could not retrieve applications from device: {str(e)}")
        
        return apps

    def list_processes(self, device_id: Optional[str] = None) -> List[Dict[str, Any]]:
        device = self.get_device(device_id)
        procs = []
        try:
            for proc in device.enumerate_processes():
                procs.append({
                    "pid": proc.pid,
                    "name": proc.name
                })
        except Exception as e:
            logger.error(f"Failed to enumerate processes on device '{device_id}': {e}")
            raise RuntimeError(f"Could not retrieve processes from device: {str(e)}")
        
        return procs

    def spawn_and_attach(self, target: str, device_id: Optional[str] = None, session_id: str = "") -> ActiveSession:
        device = self.get_device(device_id)
        try:
            pid = device.spawn([target])
            session = device.attach(pid)
            device.resume(pid)
            
            active = ActiveSession(
                session_id=session_id,
                device_id=device.id,
                target=target,
                mode="spawn",
                session=session
            )
            self.sessions[session_id] = active
            return active
        except Exception as e:
            logger.error(f"Failed to spawn target '{target}': {e}")
            raise RuntimeError(f"Frida spawn error on '{target}': {str(e)}")

    def attach_to_target(self, target: Any, device_id: Optional[str] = None, session_id: str = "") -> ActiveSession:
        device = self.get_device(device_id)
        try:
            # target can be PID (int) or process name / identifier (str)
            session = device.attach(target)
            active = ActiveSession(
                session_id=session_id,
                device_id=device.id,
                target=str(target),
                mode="attach",
                session=session
            )
            self.sessions[session_id] = active
            return active
        except Exception as e:
            logger.error(f"Failed to attach to target '{target}': {e}")
            raise RuntimeError(f"Frida attach error on '{target}': {str(e)}")

    def inject_script(self, session_id: str, script_source: str) -> Dict[str, Any]:
        if session_id not in self.sessions:
            raise KeyError(f"Session '{session_id}' not found.")

        active = self.sessions[session_id]
        if not active.is_active:
            raise RuntimeError(f"Session '{session_id}' is closed.")

        def on_message(message, data):
            active.append_output(message, data)

        try:
            script = active.session.create_script(script_source)
            script.on("message", on_message)
            script.load()
            active.scripts.append(script)
            return {"status": "loaded", "session_id": session_id}
        except Exception as e:
            logger.error(f"Failed to load Frida script into session '{session_id}': {e}")
            raise RuntimeError(f"Script injection failed: {str(e)}")

    def get_session_output(self, session_id: str) -> List[Dict[str, Any]]:
        if session_id not in self.sessions:
            raise KeyError(f"Session '{session_id}' not found.")
        return self.sessions[session_id].outputs

    def stop_session(self, session_id: str) -> Dict[str, Any]:
        if session_id not in self.sessions:
            raise KeyError(f"Session '{session_id}' not found.")
        
        active = self.sessions.pop(session_id)
        active.stop()
        return {"status": "stopped", "session_id": session_id}
