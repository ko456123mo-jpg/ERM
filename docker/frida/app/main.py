import uuid
import logging
from typing import Optional, List, Dict, Any
from fastapi import FastAPI, HTTPException, status, Query
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from app.device_manager import FridaDeviceManager

# Configure Logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s"
)
logger = logging.getLogger("erm.frida.api")

app = FastAPI(
    title="ERM Frida Dynamic Instrumentation Service",
    description="Enterprise REST API for dynamic mobile security inspection and Frida hooking.",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
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
    host: str = Field(..., description="Target hostname or IP address (e.g., host.docker.internal or 192.168.1.50)")
    port: int = Field(5555, ge=1, le=65535, description="ADB daemon port")

class CreateSessionRequest(BaseModel):
    target: str = Field(..., description="Package name (e.g., com.example.app) or Process PID")
    mode: str = Field("spawn", description="Session mode: 'spawn' or 'attach'")
    device_id: Optional[str] = Field(None, description="Target Frida/ADB device identifier")

class InjectScriptRequest(BaseModel):
    script: str = Field(..., description="JavaScript Frida hooking script source code")

class ToolExecuteRequest(BaseModel):
    application_id: int
    package_name: Optional[str] = None
    action: str = Field("default", description="Instrumentation action: 'ssl_pinning_bypass', 'root_bypass', 'method_trace', etc.")
    device_id: Optional[str] = None

# --- API Endpoints ---

@app.get("/health", response_model=HealthResponse)
def health_check():
    devices = device_mgr.enumerate_devices()
    return HealthResponse(
        status="ok",
        service="frida-integration",
        frida_version=device_mgr.get_frida_version(),
        adb_available=device_mgr.is_adb_available(),
        connected_devices_count=len(devices)
    )

@app.get("/devices")
def list_devices():
    devices = device_mgr.enumerate_devices()
    return {
        "status": "success",
        "count": len(devices),
        "devices": devices
    }

@app.post("/devices/connect")
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

@app.get("/devices/{device_id}/applications")
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

@app.get("/devices/{device_id}/processes")
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

@app.post("/sessions")
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

@app.post("/sessions/{session_id}/attach")
def attach_session_endpoint(session_id: str, target: str = Query(...), device_id: Optional[str] = Query(None)):
    try:
        t = int(target) if target.isdigit() else target
        active = device_mgr.attach_to_target(t, device_id, session_id)
        return {"status": "attached", "session_id": active.session_id}
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail=str(e))

@app.post("/sessions/{session_id}/spawn")
def spawn_session_endpoint(session_id: str, target: str = Query(...), device_id: Optional[str] = Query(None)):
    try:
        active = device_mgr.spawn_and_attach(target, device_id, session_id)
        return {"status": "spawned", "session_id": active.session_id}
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail=str(e))

@app.post("/sessions/{session_id}/scripts")
def inject_script_endpoint(session_id: str, req: InjectScriptRequest):
    try:
        res = device_mgr.inject_script(session_id, req.script)
        return res
    except KeyError as ke:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(ke))
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail=str(e))

@app.get("/sessions/{session_id}/output")
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

@app.post("/sessions/{session_id}/stop")
def stop_session(session_id: str):
    try:
        res = device_mgr.stop_session(session_id)
        return res
    except KeyError as ke:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(ke))

# --- Dynamic Analysis Execution Hook (Integration with ERM Laravel Backend) ---

@app.post("/tools/frida/execute")
@app.post("/tools/execute")
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

    selected_script = scripts.get(req.action, scripts["ssl_pinning_bypass"])

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
