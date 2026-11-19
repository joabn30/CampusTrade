
import os
import uuid
import pytest
from playwright.sync_api import Page, expect
from conftest import app_url


BASE_URL = app_url()

# Replace these with your existing QA account and book title
TEST_EMAIL = "hk8756oo@go.minnstate.edu"
TEST_PASSWORD = "Kenya@12"
TEST_BOOK_TITLE = "Software Engineering"


def login(page: Page):
    page.goto(BASE_URL)
    page.get_by_role("button", name="Sell").click()
    page.get_by_label("MinnState Email").fill(TEST_EMAIL)
    page.get_by_label("Password", exact=True).fill(TEST_PASSWORD)
    page.get_by_role("button", name="Login").click()

    # Navigate to Seller page
    page.get_by_role("button", name="Switch to Seller").click()


def select_book(page: Page, book_title: str):
    page.locator('select[name="postedBook"]').select_option(
        label=book_title
    )


def create_test_book(page: Page, book_title: str):
    page.get_by_placeholder("Book Title / Author").fill(book_title)
    page.get_by_placeholder("ISBN").fill("9780132350884")
    page.get_by_placeholder("Price").fill("25.00")
    page.locator('select[name="condition"]').select_option("New")
    page.get_by_placeholder("Course Dept.").fill("CSCI")
    page.get_by_placeholder("Contact Info").fill("qa@example.com")
    page.get_by_role("button", name="Post Book").click()


def open_buyer_page(page: Page):
    page.goto(f"{BASE_URL}/buyerpage.php")


# CT-BOOK-001
# Verify seller's existing listings appear in Books Posted dropdown
def test_display_seller_book_listings(page: Page):
    login(page)

    books_dropdown = page.locator('select[name="postedBook"]')

    expect(
        books_dropdown.get_by_role(
            "option", name=TEST_BOOK_TITLE, exact=True
        )
    ).to_have_count(1)


# CT-BOOK-002
# Verify seller can select and edit an existing listing
def test_edit_existing_book_listing(page: Page):
    login(page)

    select_book(page, TEST_BOOK_TITLE)

    page.get_by_role("button", name="Edit Book").click()

    page.get_by_placeholder("Price").fill("12.00")
    page.locator('select[name="condition"]').select_option("Used")

    page.get_by_role("button", name="Save Changes").click()

    # Reopen the book to verify saved values
    page.goto(f"{BASE_URL}/Seller_Controller.php")
    select_book(page, TEST_BOOK_TITLE)
    page.get_by_role("button", name="Edit Book").click()

    expect(
        page.get_by_placeholder("Price")
    ).to_have_value("12.00")

    expect(
        page.locator('select[name="condition"]')
    ).to_have_value("Used")


# CT-BOOK-003
# Verify edited book information appears on Buyer Page
def test_edited_book_visible_on_buyer_page(page: Page):
    login(page)

    select_book(page, TEST_BOOK_TITLE)
    page.get_by_role("button", name="Edit Book").click()

    page.get_by_placeholder("Price").fill("12.00")
    page.locator('select[name="condition"]').select_option("Used")
    page.get_by_role("button", name="Save Changes").click()

    open_buyer_page(page)

    # Replace 19 with the actual ID of your test book
    page.locator('a[href="BuyButtonPage.php?id=19"]').click()

    expect(
        page.get_by_text("Price: $12", exact=False)
    ).to_be_visible()

    expect(
        page.get_by_text("Condition: Used", exact=False)
    ).to_be_visible()


# CT-BOOK-004
# Verify seller can delete an existing book listing
def test_delete_existing_book_listing(page: Page):
    login(page)

    book_title = f"QA Delete Test {uuid.uuid4().hex[:8]}"

    create_test_book(page, book_title)

    # Return to Seller page after posting
    page.goto(f"{BASE_URL}/Seller_Controller.php")

    select_book(page, book_title)

    page.get_by_role("button", name="Delete Book").click()

    # Verify book is removed from seller dropdown
    page.goto(f"{BASE_URL}/Seller_Controller.php")

    expect(
        page.locator('select[name="postedBook"]').get_by_role(
            "option", name=book_title, exact=True
        )
    ).to_have_count(0)


# CT-BOOK-005
# Verify deleted listing no longer appears on Buyer Page
def test_deleted_book_not_visible_on_buyer_page(page: Page):
    login(page)

    book_title = f"QA Delete Test {uuid.uuid4().hex[:8]}"

    create_test_book(page, book_title)

    open_buyer_page(page)

    search = page.get_by_placeholder("Search by title or seller")
    search.fill(book_title)
    search.press("Enter")

    expect(
        page.get_by_role("heading", name=book_title, exact=True)
    ).to_be_visible()

    # Return to Seller page and delete book
    page.goto(f"{BASE_URL}/Seller_Controller.php")

    select_book(page, book_title)
    page.get_by_role("button", name="Delete Book").click()

    open_buyer_page(page)

    search = page.get_by_placeholder("Search by title or seller")
    search.fill(book_title)
    search.press("Enter")

    expect(
        page.get_by_role("heading", name=book_title, exact=True)
    ).to_have_count(0)


# CT-BOOK-006
# Verify editing or deleting without selecting a listing is prevented
def test_modify_book_without_selection(page: Page):
    login(page)

    books_dropdown = page.locator('select[name="postedBook"]')

    books_dropdown.select_option(value="")
    expect(books_dropdown).to_have_value("")

    # Attempt to edit without selecting a book
    page.get_by_role("button", name="Edit Book").click()

    # Verify no edit form is opened
    expect(
        page.get_by_role("heading", name="Edit Book")
    ).to_have_count(0)

    # Return to Seller page if needed
    page.goto(f"{BASE_URL}/Seller_Controller.php")

    books_dropdown = page.locator('select[name="postedBook"]')
    books_dropdown.select_option(value="")

    # Attempt to delete without selecting a book
    page.get_by_role("button", name="Delete Book").click()

    # Verify seller's dropdown remains available
    expect(
        page.locator('select[name="postedBook"]')
    ).to_be_visible()


# CT-BOOK-007
# Verify seller cannot modify another seller's listing
def test_unauthorized_book_modification(page: Page):
    pytest.skip(
        "Requires two QA seller accounts and backend "
        "authorization endpoint verification."
    )

    # TODO: Log in as Seller A
    # TODO: Identify a listing belonging to Seller B
    # TODO: Attempt unauthorized edit/delete requests
    # TODO: Verify server rejects both requests
    # TODO: Verify Seller B's listing remains unchanged
