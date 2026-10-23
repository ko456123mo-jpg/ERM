import logging
import os
import shutil
import subprocess
import re
from typing import Dict, List, Optional, Any

import frida

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(name)s: %(message)s")
logger = logging.getLogger("ObjectionManager")


class ObjectionManager:
    """
    Manages Objection instrumentation, environment discovery, and dynamic mobile exploration.
    """

    def __init__(self):
        self.adb_bin = shutil.which("adb")
        self.objection_bin = shutil.which("objection")
        logger.info(f"Initialized ObjectionManager (adb={self.adb_bin}, objection={self.objection_bin})")

    def is_adb_available(self) -> bool:
        return self.adb_bin is not None and os.path.exists(self.adb_bin)

    def is_objection_available(self) -> bool:
        return self.objection_bin is not None and os.path.exists(self.objection_bin)

    def get_objection_version(self) -> str:
        try:
            import objection
            return getattr(objection, "__version__", "1.11.0")
        except Exception:
            return "1.11.0"

    def get_frida_version(self) -> str:
        try:
            return frida.__version__
        except Exception:
            return "unknown"

    def enumerate_devices(self) -> List[Dict[str, Any]]:
        """
        Enumerate devices visible to Frida and ADB.
        """
        devices = []
        try:
            for dev in frida.enumerate_devices():
                devices.append({
                    "id": dev.id,
                    "name": dev.name,
                    "type": dev.type
                })
        except Exception as e:
            logger.error(f"Error enumerating Frida devices: {e}")

        # Also probe ADB devices if available
        if self.is_adb_available():
            try:
                out = subprocess.check_output([self.adb_bin, "devices"], stderr=subprocess.STDOUT, text=True, timeout=5)
                lines = [line.strip() for line in out.splitlines() if line.strip()]
                for line in lines[1:]:  # Skip 'List of devices attached'
                    parts = line.split()
                    if len(parts) >= 2 and parts[1] == "device":
                        dev_id = parts[0]
                        if not any(d["id"] == dev_id for d in devices):
                            devices.append({
                                "id": dev_id,
                                "name": f"Android ADB Device ({dev_id})",
                                "type": "adb"
                            })
            except Exception as e:
                logger.warning(f"Error querying ADB devices: {e}")

        return devices

    def connect_remote_adb(self, host: str, port: int = 5555) -> Dict[str, Any]:
        """
        Connect to a remote ADB daemon over TCP.
        """
        if not self.is_adb_available():
            raise RuntimeError("ADB binary not available in current environment")
        if not re.fullmatch(r"[A-Za-z0-9.\-_]{1,253}", host):
            raise ValueError("Invalid hostname or IP address format.")
        allowed_hosts = {item.strip().lower() for item in os.getenv("ADB_ALLOWED_HOSTS", "").split(",") if item.strip()}
        if host.lower() not in allowed_hosts:
            raise ValueError("ADB target is not in the configured allow-list.")

        target = f"{host}:{port}"
        logger.info(f"Attempting ADB connection to {target}")

        try:
            res = subprocess.run(
                [self.adb_bin, "connect", target],
                capture_output=True,
                text=True,
                timeout=10,
                check=False
            )
            output = res.stdout.strip()
            if "connected" in output.lower():
                return {"status": "connected", "target": target, "message": output}
            else:
                return {"status": "failed", "target": target, "message": output}
        except subprocess.TimeoutExpired:
            return {"status": "timeout", "target": target, "message": "Connection timed out"}
        except Exception as e:
            return {"status": "error", "target": target, "message": str(e)}

    def execute_command(self, package_name: str, action: str, device_id: Optional[str] = None) -> Dict[str, Any]:
        """
        Execute an Objection exploration routine (e.g., disable SSL pinning, bypass root, explore storage).
        """
        # Supported safe preset actions
        command_map = {
            "ssl_pinning_disable": "android sslpinning disable",
            "root_disable": "android root disable",
            "sqlite_dump": "sqlite databases list",
            "keystore_dump": "android keystore list",
            "clipboard_monitor": "android clipboard monitor",
            "file_browse": "file list",
            "env_info": "env"
        }

        chosen_cmd = command_map.get(action, "env")

        devices = self.enumerate_devices()
        has_active_device = any(d["type"] in ["usb", "remote", "adb"] for d in devices)

        if not has_active_device:
            return {
                "status": "standby",
                "message": "Objection Service is active, but no Android USB/ADB device or frida-server was detected.",
                "package_name": package_name,
                "action": action,
                "command": chosen_cmd,
                "diagnostic": {
                    "hint": "Connect an Android device with USB debugging or connect remote ADB via POST /devices/connect",
                    "available_devices": [d["name"] for d in devices]
                },
                "findings": []
            }

        cmd_args = [self.objection_bin or "objection", "-g", package_name]
        if device_id:
            cmd_args.extend(["-d", device_id])
        cmd_args.extend(["explore", "--startup-command", chosen_cmd])

        logger.info(f"Executing Objection command: {' '.join(cmd_args)}")

        try:
            res = subprocess.run(
                cmd_args,
                capture_output=True,
                text=True,
                timeout=20,
                check=False
            )

            stdout = res.stdout
            stderr = res.stderr

            findings = []
            if "Bypassing SSL Pinning" in stdout or "Hooking" in stdout:
                findings.append({
                    "title": "SSL Pinning Hook Applied",
                    "severity": "medium",
                    "description": "Objection successfully hooked SSL certificate validation classes."
                })
            if "Bypassing Root" in stdout:
                findings.append({
                    "title": "Root Detection Bypass Hook Applied",
                    "severity": "low",
                    "description": "Objection hooked common root verification methods."
                })

            return {
                "status": "success" if res.returncode == 0 else "error",
                "package_name": package_name,
                "action": action,
                "command": chosen_cmd,
                "stdout": stdout,
                "stderr": stderr,
                "returncode": res.returncode,
                "findings": findings
            }
        except subprocess.TimeoutExpired:
            return {
                "status": "timeout",
                "package_name": package_name,
                "action": action,
                "message": "Execution timed out waiting for target process."
            }
        except Exception as e:
            return {
                "status": "error",
                "package_name": package_name,
                "action": action,
                "message": str(e)
            }
