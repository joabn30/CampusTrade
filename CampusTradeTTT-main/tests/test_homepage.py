from playwright.sync_api import Page,expect

##Test fucniton 1
def test_homepage_loads(page:Page):
    page.goto("http://localhost:8080")
    expect(page.get_by_role("heading", name ="Campus Trade")).to_be_visible()
   



