import os
from pathlib import Path
from dotenv import load_dotenv
ENV_FILE = Path(__file__).resolve().parent.parent / ".env.testing"

load_dotenv(ENV_FILE, override=False)
def app_url():
    return os.getenv("BASE_URL", "http://localhost:8082")

