import os
import pytest

def app_url():
    return os.getenv("BASE_URL", "http://localhost:8082")

