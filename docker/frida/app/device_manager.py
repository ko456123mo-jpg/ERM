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

    def connect_remote_adb(self, host: str, port: int = 5555) -> Dict[str, Any]:
        # Validate host to avoid command injection
        if not re.match(r"^[a-zA-Z0-9.\-_]+$", host):
            raise ValueError("Invalid hostname or IP address format.")
        
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
