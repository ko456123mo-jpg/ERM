# ERM Platform — Docker Architecture, Integration & Operations Guide
# منصة ERM — دليل البنية التحتية، تكامل الخدمات، والتشغيل عبر Docker

---

## 1. Docker Architecture Overview / نظرة عامة على البنية التحتية

منصة **ERM (Enterprise Reverse Engineering & Mobile Security Platform)** تعتمد على بنية **Microservices** معزولة ومنظمة باستخدام **Docker & Docker Compose**. كل خدمة تعمل داخل حاوية (Container) مستقلة بحساب غير جذري (Non-root user) وتتواصل عبر شبكة داخلية آمنة (`erm-network`) بدون تعريض منافذ الخدمات الداخلية للخارج.

```
                                 Client / Browser
                                        │
                                  HTTP (Port 8000)
                                        │
                                        ▼
                      ┌───────────────────────────────────┐
                      │    ERM Web (Nginx + PHP 8.3 FPM)  │
                      │    Laravel 13 Application Layer   │
                      │      (Container: erm_app)         │
                      └─────────────────┬─────────────────┘
                                        │
                     Internal Bridge Network: `erm-network`
                                        │
         ┌──────────────────────────────┼──────────────────────────────┐
         │                              │                              │
         ▼                              ▼                              ▼
┌─────────────────┐            ┌─────────────────┐            ┌─────────────────┐
│   MySQL 8.0     │            │  Frida Service  │            │ Future Services │
│   (Database)    │            │  (Python/FastAPI│            │  (Placeholders/ │
│(Container:      │            │   Port 8001)    │            │   Abstractions) │
│  erm_db)        │            │(Container:      │            │                 │
│                 │            │  erm_frida)     │            │                 │
└─────────────────┘            └────────┬────────┘            └─────────────────┘
                                        │                             │
                                  ADB / Remote                        ├── MobSF Service
                                        │                             ├── Objection Service
                                        ▼                             └── AI Service
                              ┌───────────────────┐
                              │  Android Device / │
                              │   frida-server    │
                              └───────────────────┘
```

---

## 2. Active & Future Services / الخدمات الحالية والمستقبلية

| Service Name | Container Name | Technology / Image | Internal Port | Status / المرحلة | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Laravel App** | `erm_app` | PHP 8.3-FPM + Nginx + Node 22 (Multi-stage) | `80` (Mapped to `8000`) | **Active / تعمل** | الواجهة الرسومية ومنصة الـ Backend وإدارة الـ RBAC والتقارير |
| **Database** | `erm_db` | `mysql:8.0` | `3306` (Internal only) | **Active / تعمل** | قاعدة البيانات مع تخزين دائم `erm_mysql_data` |
| **Frida Service** | `erm_frida` | Python 3.11-slim + FastAPI + Frida + ADB | `8001` | **Active / تعمل** | خدمة الحقن الديناميكي وإدارة الجلسات والأجهزة |
| **MobSF** | `erm_mobsf` | `opensecurity/mobsf` | `8000` (Internal) | *Planned / مؤجلة* | التحليل الساكن لملفات APK (مدمج كـ Abstraction) |
| **Objection** | `erm_objection` | Python 3.11 | `8002` (Internal) | *Planned / مؤجلة* | استكشاف النظام والـ Keychain والملفات (Abstraction) |
| **AI Service** | `erm_ai` | Python / LLM Gateway | `8003` (Internal) | *Planned / مؤجلة* | توحيد النتائج وتقديم توصيات المعالجة (Abstraction) |

---

## 3. Directory Structure / هيكل الملفات

```
m:\erm-laravel/
├── docker/
│   ├── app/
│   │   ├── Dockerfile             # Multi-stage production build (Node 22 + PHP 8.3-FPM + Nginx)
│   │   ├── nginx.conf             # Nginx reverse proxy configuration with security headers
│   │   ├── supervisord.conf       # Supervisord process manager for Nginx & PHP-FPM
│   │   └── entrypoint.sh          # Auto DB wait, migrations, keys, permissions, storage link
│   └── frida/
│       ├── Dockerfile             # Python 3.11-slim + ADB client + Non-root user
│       ├── requirements.txt       # fastapi, uvicorn, frida, frida-tools, pydantic, httpx
│       ├── .dockerignore          # Python cache and temporary files ignore
│       └── app/
│           ├── __init__.py
│           ├── main.py            # FastAPI endpoints (health, devices, sessions, scripts, execute)
│           └── device_manager.py  # Frida & ADB device abstraction and output stream buffer
├── app/
│   ├── Services/
│   │   ├── Contracts/
│   │   │   └── SecurityToolServiceInterface.php # Unified interface for all security tools
│   │   ├── FridaService.php       # Laravel client for Frida Microservice
│   │   ├── MobSfService.php       # Abstraction for future MobSF integration
│   │   ├── ObjectionService.php   # Abstraction for future Objection integration
│   │   ├── AiService.php          # Abstraction for future AI analysis engine
│   │   └── Analysis/
│   │       ├── FindingNormalizer.php # Normalizes raw findings into standard schema
│   │       └── ResultAggregator.php  # Aggregates findings and computes composite risk score
├── config/
│   └── services.php               # Microservices config (frida, mobsf, objection, ai, fastapi)
├── docker-compose.yml             # Orchestration for app, db, frida, volumes, and networks
├── .dockerignore                  # Root Docker build ignore
├── .env.example                   # Clean template for environment variables
└── DOCKER.md                      # This comprehensive documentation
```

---

## 4. Quick Start & Operations / أوامر التشغيل السريعة

### 1. الإعداد الأولي (Initial Setup)
```bash
# 1. انسخ ملف الإعدادات
cp .env.example .env

# 2. بناء وتشغيل الحاويات في الخلفية
docker compose build
docker compose up -d
```

### 2. التحقق من حالة الخدمات (Check Status)
```bash
# عرض الحاويات وحالة الـ Healthchecks
docker compose ps
```

### 3. متابعة السجلات (View Logs)
```bash
# عرض سجلات جميع الخدمات
docker compose logs -f

# عرض سجلات خدمة معينة
docker compose logs -f app
docker compose logs -f frida
docker compose logs -f db
```

### 4. إيقاف المنصة (Stop Platform)
```bash
# إيقاف الحاويات مع الحفاظ على البيانات في Volumes
docker compose down

# إيقاف الحاويات وحذف الـ Volumes بالكامل (تحذير: سيحذف قاعدة البيانات)
docker compose down -v
```

---

## 5. Configuration & Environment Variables / المتغيرات البيئية

يحتوي ملف `.env.example` على كافة المفاتيح المطلوبة بدون أي أسرار:

```dotenv
# Application
APP_NAME="ERM Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000
APP_PORT=8000

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=erm_platform
DB_USERNAME=erm_user
DB_PASSWORD=your_secure_password_here
DB_ROOT_PASSWORD=your_secure_root_password_here

# Frida Integration Service
FRIDA_SERVICE_URL=http://frida:8001
FRIDA_PORT=8001
FRIDA_TIMEOUT=30

# Startup Automations
RUN_MIGRATIONS=true
SEED_DATABASE=true
```

---

## 6. Frida Service Architecture & Android/ADB Setup

### المعمارية والفصل التقني:
1. **داخل حاوية Frida (`erm_frida`)**:
   - يعمل خادم **FastAPI** على المنفذ الداخلي `8001`.
   - يحتوي على عميل **ADB Client** ومكتبة **Frida Python Bindings**.
   - لا يحتوي على صلاحيات Root على نظام المضيف (Non-root user `ermuser`).
   - لا ينفذ أوامر نظام عشوائية (No arbitrary shell commands from users).

2. **على جهاز المضيف أو الشبكة (Host / Physical Device / Emulator)**:
   - جهاز أندرويد حقيقي أو محاكي (AVD, Genymotion, Corellium).
   - يتم تفعيل خيار **USB Debugging** والـ **Wireless Debugging (ADB over TCP/IP)**.
   - يعمل خادم **`frida-server`** على جهاز الأندرويد.

### كيفية ربط جهاز Android بالحاوية:
- **عبر Remote ADB / Network**:
  ```bash
  # من واجهة ERM أو عبر API:
  POST /devices/connect
  {
      "host": "host.docker.internal", # أو IP جهاز الأندرويد
      "port": 5555
  }
  ```
- **عبر Frida Remote Device**:
  يمكن لـ Frida الاتصال مباشرة بـ `frida-server` الذي يستمع على `0.0.0.0:27042` على جهاز الأندرويد.

### ماذا يحدث عند عدم وجود جهاز متصل؟ (Graceful Fallback)
لا ينهار النظام! إذا لم يكن هناك جهاز متصل، تُرجع الخدمة استجابة منظمة `status: "standby"` مع تشخيص واضح وإرشادات للمستخدم، ويستمر نظام ERM ولوحة التحكم في العمل بكامل كفاءتهما.

---

## 7. Frida Service REST API Reference

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/health` | التحقق من صحة الخدمة، إصدار Frida، حالة ADB، وعدد الأجهزة |
| `GET` | `/devices` | جلب قائمة الأجهزة المتصلة (USB, Remote, Local, Emulator) |
| `POST` | `/devices/connect` | الاتصال بجهاز عن بُعد عبر ADB (`host`, `port`) |
| `GET` | `/devices/{id}/applications` | استعراض التطبيقات المثبتة على الجهاز المحدد |
| `GET` | `/devices/{id}/processes` | استعراض العمليات الجارية (Running Processes) على الجهاز |
| `POST` | `/sessions` | إنشاء جلسة حقن ديناميكي (`target`, `mode: spawn|attach`, `device_id`) |
| `POST` | `/sessions/{id}/scripts` | حقن سكربت Frida JavaScript بأمان واستقبال الإشارات |
| `GET` | `/sessions/{id}/output` | قراءة سجلات ومخرجات الجلسة |
| `POST` | `/sessions/{id}/stop` | إنهاء الجلسة وفصل الحقن |
| `POST` | `/tools/frida/execute` | تنفيذ سيناريوهات فحص جاهزة (SSL bypass, Root detection check) |

---

## 8. Result Normalization & Aggregation / توحيد النتائج والربط المستقبلي

تم بناء طبقة تجريد `FindingNormalizer` و `ResultAggregator` لتحويل مخرجات الأدوات المختلفة إلى هيكل بيانات موحد:

```json
{
  "source": "frida",
  "finding": "SSL Validation Intercepted",
  "severity": "medium",
  "confidence": 0.90,
  "evidence": [
    "Hooking TrustManager and OkHttp for SSL Pinning Bypass..."
  ],
  "metadata": {},
  "timestamp": "2026-08-28T15:30:00+03:00"
}
```

يقوم `ResultAggregator` بحساب **Composite Risk Score (0-100)** وتحديد مستوى الخطورة (`Critical`, `High`, `Medium`, `Low`, `Safe`)، وتجهيز حزمة البيانات الموحدة لإرسالها مستقبلاً إلى **AI Service**.

---

## 9. Security & Hardening / إجراءات الأمان والعزل

- **Non-Root Containers**: حاوية Frida تعمل بمستخدم `ermuser (UID 1000)`، وحاوية Laravel تعمل بمستخدم `www-data`.
- **Minimal Attack Surface**: استخدام صور Alpine و Slim لتقليل الحجم والثغرات.
- **Network Isolation**: قاعدة البيانات وخدمة Frida معزولتان داخل `erm-network` ولا يمكن الوصول لقاعدة البيانات من خارج المضيف.
- **Input Sanitization**: التحقق الصارم من المدخلات بواسطة Pydantic و Laravel Validators لمنع الـ Command Injection.
- **No Hardcoded Secrets**: جميع كلمات المرور والمفاتيح تُمرر عبر المتغيرات البيئية حصراً.
- **Healthchecks**: فحص تلقائي ودوري لكل الحاويات لضمان عدم توجيه الطلبات لخدمة غير جاهزة.

---

## 10. Data Persistence / استمرارية البيانات

- `erm_mysql_data`: يحتفظ ببيانات جداول MySQL وحسابات المستخدمين والـ Audit Logs.
- `erm_app_storage`: يحتفظ بملفات APK المرفوعة والتقارير المُنشأة وسجلات Laravel.
- `erm_frida_data`: يحتفظ ببيانات جلسات وسكربتات Frida المؤقتة.

---

## 11. Troubleshooting / استكشاف الأخطاء وحلها

### 1. Laravel لا يستطيع الاتصال بقاعدة البيانات
- تأكد أن حاوية `erm_db` في حالة `healthy`:
  ```bash
  docker compose ps
  ```
- تأكد أن `DB_HOST=db` داخل الحاوية.

### 2. خدمة Frida تظهر في حالة `Standby`
- تأكد من تشغيل الحاوية:
  ```bash
  docker compose logs frida
  ```
- اختبر فحص الصحة يدوياً:
  ```bash
  curl http://localhost:8001/health
  ```

### 3. عدم ظهور جهاز Android في Frida
- تأكد من تشغيل `frida-server` على الهاتف أو المحاكي.
- استخدم نقطة النهاية `POST /devices/connect` لربط الـ ADB بالـ IP الخاص بالجهاز.
