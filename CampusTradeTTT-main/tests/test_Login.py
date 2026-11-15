
import os
import uuid
from playwright.sync_api import Page, expect

BASE_URL = "http://localhost:8080"


def get_credentials():
    return {
        "email": os.environ["QA_SELLER_EMAIL"],
        "password": os.environ["QA_SELLER_PASSWORD"]
    }


def open_login(page: Page):
    page.goto(BASE_URL)
    page.get_by_role("link", name="Login").click()

    expect(
        page.get_by_role("heading", name="Login to CampusTrade")
    ).to_be_visible()


def fill_login(page: Page, email: str, password: str):
    page.get_by_label("MinnState Email").fill(email)
    page.get_by_label("Password", exact=True).fill(password)
    page.get_by_role("button", name="Login").click()


# CT-AUTH-005: Login with valid credentials
def test_validLogincredentials(page: Page):
    account = get_credentials()

    open_login(page)
    fill_login(page, account["email"], account["password"])

    expect(
        page.locator("a.btn.logout")

    ).to_be_visible()


# CT-AUTH-006: Login with incorrect password
def test_InvalidPassword(page: Page):
    account = get_credentials()
    invalid_password = uuid.uuid4().hex

    open_login(page)
    fill_login(page, account["email"], invalid_password)

    expect(
        page.get_by_role("heading", name="Login to CampusTrade")
    ).to_be_visible()

    expect(
        page.get_by_role("link", name="Logout")
    ).to_have_count(0)


# CT-AUTH-007: Login with unregistered account
def test_UnregistedAccount(page: Page):
    account = get_credentials()
    username = uuid.uuid4().hex[:12]
    unregistered_email = f"{username}@go.minnstate.edu"

    open_login(page)
    fill_login(page, unregistered_email, account["password"])

    expect(
        page.get_by_role("heading", name="Login to CampusTrade")
    ).to_be_visible()

    expect(
        page.get_by_role("link", name="Logout")
    ).to_have_count(0)


# CT-AUTH-008: Login with empty credentials
def test_emptycredentials(page: Page):
    open_login(page)

    email_field = page.get_by_label("MinnState Email")
    password_field = page.get_by_label("Password", exact=True)

    email_field.fill("")
    password_field.fill("")

    page.get_by_role("button", name="Login").click()

    expect(
        page.get_by_role("heading", name="Login to CampusTrade")
    ).to_be_visible()

    expect(
        page.get_by_role("link", name="Logout")
    ).to_have_count(0)


# CT-AUTH-009: Logout from authenticated session
def test_logout(page: Page):
    account = get_credentials()

    open_login(page)
    fill_login(page, account["email"], account["password"])

    logout_link = page.locator("a.btn.logout")

    expect(logout_link).to_be_visible()
    logout_link.click()

    # Verify session no longer grants access
    page.goto(f"{BASE_URL}/SellerPage.php")

    expect(
        page.get_by_role("heading", name="Login to CampusTrade")
    ).to_be_visible()
