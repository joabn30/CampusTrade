from playwright.sync_api import Page,expect
from conftest import app_url

##Test fucniton 1
def test_homepage_loads(page:Page):
    page.goto(app_url())
    expect(page.get_by_role("heading", name ="Campus Trade")).to_be_visible()
   



