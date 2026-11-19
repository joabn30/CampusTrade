
from playwright.sync_api import Page, expect
from tests.helpers.db_helper import get_test_account
import os
from conftest import app_url

BASE_URL = app_url()



def login_user(page: Page):
    email = os.environ["QA_SELLER_EMAIL"]
    password = os.environ["QA_SELLER_PASSWORD"]

    page.goto(BASE_URL)
    page.get_by_role("link", name="Login").click()

    page.get_by_label("MinnState Email").fill(email)
    page.get_by_label("Password", exact=True).fill(password)

    page.get_by_role("button", name="Login").click()

# Test Case 1
# Buy button access with active user session
def test_buyauthorizedaccess(page: Page):
    login_user(page)

    page.get_by_role("link", name="Home").click()
    page.get_by_role("button", name="Buy").click()

    expect(
        page.get_by_role("link", name="Switch to Seller")
    ).to_be_visible()


# Test Case 2
# Sell button access with active user session
def test_sellauthorizedaccess(page: Page):
    login_user(page)

    page.get_by_role("link", name="Home").click()
    page.get_by_role("button", name="Sell").click()

    expect(
        page.get_by_role("link", name="Switch to Buyer")
    ).to_be_visible()


# Test Case 3
# Buy button access without active session
def test_buyunauthorizedaccess(page: Page):
    page.goto(BASE_URL)

    page.get_by_role("button", name="Buy").click()

    expect(
        page.get_by_role("heading", name="Login to CampusTrade")
    ).to_be_visible()


# Test Case 4
# Sell button access without active session
def test_sellunauthorizedaccess(page: Page):
    page.goto(BASE_URL)

    # Log out only if a session exists
    logout_link = page.get_by_role("link", name="Logout", exact=True)

    if logout_link.is_visible():
        logout_link.click()
        page.get_by_role("link", name="Home").click()

    # Always test unauthorized access
    page.get_by_role("button", name="Sell").click()

    expect(
        page.get_by_role("heading", name="Login to CampusTrade")
    ).to_be_visible()

# Test Case 5
# Switch from Buyer page to Seller page
def test_switchtoSeller(page: Page):
    login_user(page)

    page.goto(f"{BASE_URL}/buyerpage.php")

    page.get_by_role("link", name="Switch to Seller").click()

    expect(
        page.get_by_role("heading", name="Post a Book")
    ).to_be_visible()


# Test Case 6
# Switch from Seller page to Buyer page
def test_switchtoBuyer(page: Page):
    login_user(page)

    #Why did you all this line it's useless
    #page.goto(f"{BASE_URL}/Sellerpage.php")
    #page.get_by_role("link", name="Switch to Seller").click()

    page.get_by_role("link", name="Switch to Buyer").click()

    expect(
        page.get_by_placeholder("Search by title or seller")
    ).to_be_visible()
