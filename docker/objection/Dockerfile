FROM python:3.11-slim

# Set environment
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    DEBIAN_FRONTEND=noninteractive \
    OBJECTION_HOME=/home/ermuser/.objection

# Install Android Debug Bridge (adb), networking utilities, and build tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    adb \
    curl \
    netcat-openbsd \
    gcc \
    g++ \
    libffi-dev \
    && rm -rf /var/lib/apt/lists/*

# Create dedicated non-root user and group
RUN groupadd -g 1000 ermgroup && \
    useradd -u 1000 -g ermgroup -m -s /bin/bash ermuser && \
    mkdir -p /app /home/ermuser/.objection /home/ermuser/.android /run/erm && \
    chown -R ermuser:ermgroup /app /home/ermuser

WORKDIR /app

# Copy dependency definition and install
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy application source code
COPY app/ ./app/

RUN chown -R ermuser:ermgroup /app

# Switch to non-root user
USER ermuser

EXPOSE 8003

HEALTHCHECK --interval=20s --timeout=5s --start-period=10s --retries=3 \
    CMD ["python", "-c", "import socket; s=socket.create_connection(('127.0.0.1', 8003),2); s.close()"]

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8003"]
