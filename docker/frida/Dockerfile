FROM python:3.11-slim

# Prevent Python from writing .pyc and buffer stdout/stderr
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

# Install system dependencies: ADB client, curl for healthcheck, and CA certificates
RUN apt-get update && apt-get install -y --no-install-recommends \
    adb \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Create non-root user and group
RUN groupadd -g 1000 ermgroup && \
    useradd -u 1000 -g ermgroup -m -s /bin/bash ermuser && \
    mkdir -p /home/ermuser/.android /run/erm /opt/frida && \
    chown -R ermuser:ermgroup /home/ermuser/.android && \
    chmod 0755 /run/erm /opt/frida

# Set working directory
WORKDIR /app

# Install Python dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy service application code
COPY app/ ./app/

# Set ownership to non-root user
RUN chown -R ermuser:ermgroup /app

# Switch to non-root user
USER ermuser

# Expose Frida service API port
EXPOSE 8001

# Healthcheck configuration
HEALTHCHECK --interval=20s --timeout=5s --start-period=10s --retries=3 \
    CMD ["python", "-c", "import socket; s=socket.create_connection(('127.0.0.1', 8001),2); s.close()"]

# Start FastAPI application
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001"]
