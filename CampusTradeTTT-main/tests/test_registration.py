
from playwright.sync_api import Page, expect
from conftest import app_url
import uuid
import os


BASE_URL = app_url()
PASSWORD = os.getenv("QA_REGISTRATION_PASSWORD", "Kenya@12")


# Generate a unique 6-character username
def generate_username():
    return uuid.uuid4().hex[:6]


def generate_email():
    return f"{generate_username()}@go.minnstate.edu"


# Open the registration page
def open_registration(page: Page):
    page.goto(BASE_URL)
    page.get_by_role("link", name="Sign Up").click()

    expect(
        page.get_by_role("heading", name="Create an account")
    ).to_be_visible()


# Reusable registration form helper
def fill_registration(
    page: Page,
    first_name="Marthe",
    last_name="Odoo",
    email=None,
    password=PASSWORD,
    confirm_password=PASSWORD,
):
    if email is None:
        email = generate_email()

    page.get_by_role("textbox", name="First Name").fill(first_name)
    page.get_by_role("textbox", name="Last Name").fill(last_name)
    page.get_by_role("textbox", name="MinnState Email").fill(email)
    page.get_by_role("textbox", name="School Name").fill(
        "Metropolitan State University"
    )
    page.get_by_role("textbox", name="Major").fill("Computer Science")
    page.get_by_role("textbox", name="State/City").fill("St Paul")

    page.get_by_role("combobox", name="Role").select_option(
        label="Student"
    )

    page.get_by_label("Password", exact=True).fill(password)
    page.get_by_label("Confirm Password", exact=True).fill(
        confirm_password
    )


def submit_registration(page: Page):
    page.get_by_role("button", name="Sign Up").click()


def expect_registration_rejected(page: Page):
    expect(
        page.get_by_role("heading", name="Create an account")
    ).to_be_visible()


# CT-AUTH-001: Register using valid user information
def test_validregistration(page: Page):
    open_registration(page)

    fill_registration(page, email=generate_email())
    submit_registration(page)

    expect(
        page.get_by_role("heading", name="Login to CampusTrade")
    ).to_be_visible()


# CT-AUTH-002A: Attempt registration without first and last name
def test_emptynamefield(page: Page):
    open_registration(page)

    fill_registration(
        page,
        first_name="",
        last_name="",
    )

    submit_registration(page)
    expect_registration_rejected(page)


# CT-AUTH-002B: Attempt registration without MinnState email
def test_emptyemailfield(page: Page):
    open_registration(page)

    fill_registration(page, email="")

    submit_registration(page)
    expect_registration_rejected(page)


# CT-AUTH-002C: Attempt registration without password
def test_emptypasswordfields(page: Page):
    open_registration(page)

    fill_registration(
        page,
        password="",
        confirm_password="",
    )

    submit_registration(page)
    expect_registration_rejected(page)


# CT-AUTH-003: Attempt registration using an already registered email
def test_registeredUser(page: Page):
    existing_email = "hk8756oo@go.minnstate.edu"

    open_registration(page)

    fill_registration(page, email=existing_email)
    submit_registration(page)

    expect_registration_rejected(page)

# CT-AUTH-004: Attempt registration with malformed email
def test_malformedemail(page: Page):
    open_registration(page)

    fill_registration(
        page,
        email=f"{generate_username()}minnstate.edu",
    )

    submit_registration(page)
    expect_registration_rejected(page)










