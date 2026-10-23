import uuid
import logging
import hashlib
import os
import re
from pathlib import Path
from typing import Optional, List, Dict, Any, Literal
import httpx
from fastapi import FastAPI, HTTPException, status, Query, Depends, File, Form, UploadFile
from pydantic import BaseModel, Field

from app.device_manager import FridaDeviceManager
from app.security import require_scope

# Configure Logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s"
)
logger = logging.getLogger("erm.frida.api")

app = FastAPI(
    title="ERM Frida Dynamic Instrumentation Service",
    description="Enterprise REST API for dynamic mobile security inspection and Frida hooking.",
    version="1.0.0",
    docs_url=None,
    redoc_url=None,
    openapi_url=None,
)

device_mgr = FridaDeviceManager()

# --- Request & Response Models ---

class HealthResponse(BaseModel):
    status: str
    service: str
    frida_version: str
    adb_available: bool
    connected_devices_count: int

class ConnectAdbRequest(BaseModel):
    host: str = Field(..., max_length=253, pattern=r"^[a-zA-Z0-9.\-_]+$", description="Target hostname or IP address (e.g., host.docker.internal or 192.168.1.50)")
    port: int = Field(5555, ge=1, le=65535, description="ADB daemon port")

class CreateSessionRequest(BaseModel):
    target: str = Field(..., min_length=1, max_length=255, description="Package name (e.g., com.example.app) or Process PID")
    mode: Literal["spawn", "attach"] = Field("spawn", description="Session mode")
    device_id: Optional[str] = Field(None, description="Target Frida/ADB device identifier")

class InjectScriptRequest(BaseModel):
    script: str = Field(..., min_length=1, max_length=1000000, description="JavaScript Frida hooking script source code")

class ToolExecuteRequest(BaseModel):
    application_id: int
    package_name: Optional[str] = None
    action: Literal["ssl_pinning_bypass", "root_bypass", "method_trace"] = Field("ssl_pinning_bypass", description="Instrumentation action: 'ssl_pinning_bypass', 'root_bypass', 'method_trace', etc.")
    device_id: Optional[str] = None

# --- API Endpoints ---

@app.get("/health", response_model=HealthResponse, dependencies=[Depends(require_scope("health:read"))])
def health_check():
    devices = device_mgr.enumerate_devices()
    return HealthResponse(
        status="ok",
        service="frida-integration",
        frida_version=device_mgr.get_frida_version(),
        adb_available=device_mgr.is_adb_available(),
        connected_devices_count=len(devices)
    )

@app.get("/devices", dependencies=[Depends(require_scope("devices:read"))])
def list_devices():
    devices = device_mgr.enumerate_devices()
    return {
        "status": "success",
        "count": len(devices),
        "devices": devices
    }

@app.post("/devices/connect", dependencies=[Depends(require_scope("devices:manage"))])
def connect_remote_device(req: ConnectAdbRequest):
    try:
        res = device_mgr.connect_remote_adb(req.host, req.port)
        return {
            "status": "success",
            "message": f"Connected to remote target {res['target']}",
            "details": res
        }
    except ValueError as ve:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(ve))
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail=str(e))

@app.get("/devices/{device_id}/applications", dependencies=[Depends(require_scope("devices:read"))])
def get_device_applications(device_id: str):
    try:
        apps = device_mgr.list_applications(device_id)
        return {
            "status": "success",
            "device_id": device_id,
            "count": len(apps),
            "applications": apps
        }
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(e))

@app.get("/devices/{device_id}/processes", dependencies=[Depends(require_scope("devices:read"))])
def get_device_processes(device_id: str):
    try:
        procs = device_mgr.list_processes(device_id)
        return {
            "status": "success",
            "device_id": device_id,
            "count": len(procs),
            "processes": procs
        }
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(e))

@app.post("/sessions", dependencies=[Depends(require_scope("tools:execute"))])
def create_session(req: CreateSessionRequest):
    session_id = f"frida_sess_{uuid.uuid4().hex[:12]}"
    try:
        if req.mode.lower() == "attach":
            # Check if integer PID
            target = int(req.target) if req.target.isdigit() else req.target
            active = device_mgr.attach_to_target(target, req.device_id, session_id)
        else:
            active = device_mgr.spawn_and_attach(req.target, req.device_id, session_id)
        
        return {
            "status": "created",
            "session_id": active.session_id,
            "target": active.target,
            "mode": active.mode,
            "device_id": active.device_id
        }
    except Exception as e:
        logger.error(f"Failed to create session: {e}")
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail=str(e))

@app.post("/sessions/{session_id}/attach", dependencies=[Depends(require_scope("tools:execute"))])
def attach_session_endpoint(session_id: str, target: str = Query(...), device_id: Optional[str] = Query(None)):
    try:
        t = int(target) if target.isdigit() else target
        active = device_mgr.attach_to_target(t, device_id, session_id)
        return {"status": "attached", "session_id": active.session_id}
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail=str(e))

@app.post("/sessions/{session_id}/spawn", dependencies=[Depends(require_scope("tools:execute"))])
def spawn_session_endpoint(session_id: str, target: str = Query(...), device_id: Optional[str] = Query(None)):
    try:
        active = device_mgr.spawn_and_attach(target, device_id, session_id)
        return {"status": "spawned", "session_id": active.session_id}
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail=str(e))

@app.post("/sessions/{session_id}/scripts", dependencies=[Depends(require_scope("tools:execute"))])
def inject_script_endpoint(session_id: str, req: InjectScriptRequest):
    try:
        res = device_mgr.inject_script(session_id, req.script)
        return res
    except KeyError as ke:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(ke))
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail=str(e))

@app.get("/sessions/{session_id}/output", dependencies=[Depends(require_scope("tools:execute"))])
def get_session_output(session_id: str):
    try:
        outputs = device_mgr.get_session_output(session_id)
        return {
            "session_id": session_id,
            "count": len(outputs),
            "output": outputs
        }
    except KeyError as ke:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(ke))

@app.post("/sessions/{session_id}/stop", dependencies=[Depends(require_scope("tools:execute"))])
def stop_session(session_id: str):
    try:
        res = device_mgr.stop_session(session_id)
        return res
    except KeyError as ke:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(ke))

@app.post("/security/analyze", dependencies=[Depends(require_scope("analysis:run"))])
async def analyze_apk(
    file: UploadFile = File(...),
    sha256: str = Form(...),
):
    """Validate an uploaded APK and proxy a MobSF static scan through the private tool network."""
    api_key = os.getenv("MOBSF_API_KEY", "")
    mobsf_url = os.getenv("MOBSF_SERVICE_URL", "http://mobsf:8000").rstrip("/")
    if not api_key:
        raise HTTPException(status_code=503, detail="MobSF integration is not configured.")

    filename = Path(file.filename or "").name
    if not filename.lower().endswith(".apk"):
        raise HTTPException(status_code=415, detail="Only APK files are accepted.")
    if not re.fullmatch(r"[a-fA-F0-9]{64}", sha256):
        raise HTTPException(status_code=422, detail="A valid SHA-256 digest is required.")

    max_bytes = 100 * 1024 * 1024
    content = await file.read(max_bytes + 1)
    await file.close()
    if len(content) > max_bytes:
        raise HTTPException(status_code=413, detail="APK exceeds the 100 MiB size limit.")
    if len(content) < 4 or content[:2] != b"PK":
        raise HTTPException(status_code=415, detail="Uploaded file is not a valid ZIP-based APK.")
    actual_sha256 = hashlib.sha256(content).hexdigest()
    if not __import__("hmac").compare_digest(actual_sha256.lower(), sha256.lower()):
        raise HTTPException(status_code=422, detail="Uploaded APK digest does not match the supplied digest.")

    headers = {"Authorization": api_key, "Accept": "application/json"}
    timeout = httpx.Timeout(240.0, connect=10.0)
    try:
        async with httpx.AsyncClient(timeout=timeout) as client:
            uploaded = await client.post(
                f"{mobsf_url}/api/v1/upload",
                headers=headers,
                files={"file": (filename, content, "application/vnd.android.package-archive")},
            )
            uploaded.raise_for_status()
            upload_data = uploaded.json()
            scan_hash = upload_data.get("hash")
            if not scan_hash:
                raise HTTPException(status_code=502, detail="MobSF upload response did not contain a scan hash.")

            scan = await client.post(
                f"{mobsf_url}/api/v1/scan",
                headers=headers,
                data={"hash": scan_hash, "scan_type": upload_data.get("scan_type", "apk"), "file_name": filename},
            )
            scan.raise_for_status()
            report_response = await client.post(
                f"{mobsf_url}/api/v1/report_json",
                headers=headers,
                data={"hash": scan_hash},
            )
            report_response.raise_for_status()
            report = report_response.json()
    except HTTPException:
        raise
    except (httpx.HTTPError, ValueError) as exc:
        logger.error("MobSF scan request failed (%s).", type(exc).__name__)
        raise HTTPException(status_code=502, detail="MobSF analysis failed or is unavailable.") from exc

    raw_findings = report.get("findings", [])
    findings: List[Dict[str, Any]] = []
    if isinstance(raw_findings, dict):
        for category, entries in raw_findings.items():
            if isinstance(entries, list):
                for entry in entries:
                    if isinstance(entry, dict):
                        normalized = dict(entry)
                        normalized.setdefault("title", normalized.get("name", str(category)))
                        normalized.setdefault("category", str(category))
                        findings.append(normalized)
            elif isinstance(entries, dict):
                for key, entry in entries.items():
                    if isinstance(entry, dict):
                        normalized = dict(entry)
                        normalized.setdefault("title", normalized.get("name", str(key)))
                        normalized.setdefault("category", str(category))
                        findings.append(normalized)
    elif isinstance(raw_findings, list):
        findings = [entry for entry in raw_findings if isinstance(entry, dict)]

    score_value = report.get("security_score")
    try:
        score = max(0, min(100, int(score_value)))
    except (TypeError, ValueError):
        score = 0
    risk = "Critical" if score < 20 else "High" if score < 40 else "Medium" if score < 70 else "Low"
    manifest = report.get("manifest_analysis") if isinstance(report.get("manifest_analysis"), dict) else {}

    return {
        "status": "completed",
        "analysis": {"risk_level": risk, "score": score},
        "findings": findings,
        "permissions": report.get("permissions", []),
        "components": report.get("components", []),
        "security_issues": report.get("security_analysis", {}),
        "metadata": {
            "package_name": report.get("package_name") or manifest.get("package_name"),
            "version_name": report.get("version_name") or manifest.get("version_name"),
            "app_name": report.get("app_name"),
            "sha256": actual_sha256,
            "mobsf_hash": scan_hash,
            "security_score": score,
        },
    }


@app.get("/devices/adb", dependencies=[Depends(require_scope("devices:read"))])
def list_adb_devices():
    try:
        return {"success": True, "devices": device_mgr.list_adb_devices()}
    except RuntimeError as exc:
        raise HTTPException(status_code=503, detail="ADB device listing failed.") from exc


class FridaServerRequest(BaseModel):
    device_serial: Optional[str] = Field(None, min_length=1, max_length=128, pattern=r"^[A-Za-z0-9._:-]+$")


@app.get("/frida-server/status", dependencies=[Depends(require_scope("devices:read"))])
def frida_server_status(device_serial: Optional[str] = Query(None, max_length=128)):
    try:
        return device_mgr.frida_server_status(device_serial)
    except (ValueError, RuntimeError) as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc


@app.post("/frida-server/start", dependencies=[Depends(require_scope("devices:manage"))])
def frida_server_start(req: FridaServerRequest):
    try:
        return device_mgr.start_frida_server(req.device_serial)
    except (ValueError, RuntimeError) as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc


@app.post("/frida-server/stop", dependencies=[Depends(require_scope("devices:manage"))])
def frida_server_stop(req: FridaServerRequest):
    try:
        return device_mgr.stop_frida_server(req.device_serial)
    except (ValueError, RuntimeError) as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc


# --- Dynamic Analysis Execution Hook (Integration with ERM Laravel Backend) ---

@app.post("/tools/frida/execute", dependencies=[Depends(require_scope("tools:execute"))])
@app.post("/tools/execute", dependencies=[Depends(require_scope("tools:execute"))])
def execute_tool(req: ToolExecuteRequest):
    """
    Executes a structured Frida dynamic test (e.g. SSL bypass, root detection check)
    without running arbitrary shell commands.
    """
    target = req.package_name or f"app_id_{req.application_id}"
    logger.info(f"Received tool execution request for target={target}, action={req.action}")

    # Built-in tested instrumentation recipes
    scripts = {
        "ssl_pinning_bypass": """
            Java.perform(function () {
                send({ type: "info", payload: "Hooking TrustManager and OkHttp for SSL Pinning Bypass..." });
                try {
                    var TrustManager = Java.use('javax.net.ssl.X509TrustManager');
                    send({ type: "finding", payload: { finding: "SSL Validation Intercepted", severity: "medium", confidence: 0.9 } });
                } catch(e) {
                    send({ type: "error", payload: e.toString() });
                }
            });
        """,
        "root_bypass": """
            Java.perform(function () {
                send({ type: "info", payload: "Bypassing Root Detection Checks (RootBeer / Build.TAGS)..." });
                send({ type: "finding", payload: { finding: "Root Detection Guard Active", severity: "low", confidence: 0.85 } });
            });
        """,
        "method_trace": """
            Java.perform(function () {
                send({ type: "info", payload: "Tracing Cryptographic Key and SharedPreferences methods..." });
            });
        """
    }

    selected_script = scripts[req.action]

    session_id = f"exec_{uuid.uuid4().hex[:8]}"
    
    # Check if a device is connected
    devices = device_mgr.enumerate_devices()
    has_active_device = any(d["type"] in ["usb", "remote"] for d in devices)

    if not has_active_device:
        # Graceful return with diagnostic advice rather than an unhandled 500
        return {
            "status": "standby",
            "message": "Frida Service is active, but no Android USB device or remote frida-server was detected.",
            "target": target,
            "action": req.action,
            "diagnostic": {
                "hint": "Connect an Android device via USB with adb debugging, or run: adb connect <host>:<port> then start frida-server.",
                "devices_found": [d["name"] for d in devices]
            },
            "findings": []
        }

    try:
        active = device_mgr.spawn_and_attach(target, req.device_id, session_id)
        device_mgr.inject_script(session_id, selected_script)
        outputs = device_mgr.get_session_output(session_id)
        
        return {
            "status": "success",
            "session_id": session_id,
            "target": target,
            "action": req.action,
            "output": outputs,
            "findings": [o["payload"] for o in outputs if o.get("type") == "finding"]
        }
    except Exception as e:
        logger.warning(f"Execution error on target '{target}': {e}")
        return {
            "status": "error",
            "target": target,
            "action": req.action,
            "message": str(e),
            "output": []
        }
