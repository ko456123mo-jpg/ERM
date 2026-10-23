import logging
from typing import Optional, List, Dict, Any

from fastapi import FastAPI, HTTPException, status
from pydantic import BaseModel, Field

from app.objection_manager import ObjectionManager

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(name)s: %(message)s")
logger = logging.getLogger("ObjectionAPI")

app = FastAPI(
    title="ERM Objection Security Service",
    description="Microservice for mobile runtime exploration and instrumentation via Objection and Frida.",
    version="1.0.0"
)

mgr = ObjectionManager()


class HealthResponse(BaseModel):
    status: str
    service: str
    objection_version: str
    frida_version: str
    adb_available: bool
    connected_devices_count: int


class ConnectRequest(BaseModel):
    host: str
    port: int = Field(5555, ge=1, le=65535)


class ExecuteRequest(BaseModel):
    application_id: Optional[int] = None
    package_name: Optional[str] = Field("com.example.app", description="Target Android application package name")
    action: str = Field("ssl_pinning_disable", description="Action: ssl_pinning_disable, root_disable, sqlite_dump, keystore_dump, file_browse, env_info")
    device_id: Optional[str] = None


@app.get("/health", response_model=HealthResponse)
def health_check():
    devices = mgr.enumerate_devices()
    return HealthResponse(
        status="ok",
        service="objection-integration",
        objection_version=mgr.get_objection_version(),
        frida_version=mgr.get_frida_version(),
        adb_available=mgr.is_adb_available(),
        connected_devices_count=len(devices)
    )


@app.get("/devices")
def list_devices():
    devices = mgr.enumerate_devices()
    return {
        "status": "success",
        "count": len(devices),
        "devices": devices
    }


@app.post("/devices/connect")
def connect_adb(req: ConnectRequest):
    res = mgr.connect_remote_adb(req.host, req.port)
    return res


@app.post("/tools/objection/execute")
@app.post("/tools/execute")
def execute_tool(req: ExecuteRequest):
    pkg = req.package_name or "com.example.app"
    logger.info(f"Received Objection execute request for package={pkg}, action={req.action}")
    res = mgr.execute_command(pkg, req.action, req.device_id)
    return res
