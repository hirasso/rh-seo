import { test, expect } from "@playwright/test";

test("the front page has SEO meta tags", async ({ page }) => {
  await page.goto("/");
  await expect(page.locator('meta[property="og:site_name"]')).toHaveCount(1);
  await expect(page.locator('meta[property="og:url"]')).toHaveCount(1);
});

test("the sitemap index has no users sitemap", async ({ request }) => {
  const response = await request.get("/wp-sitemap.xml");
  expect(response.ok()).toBe(true);
  const xml = await response.text();
  expect(xml).toContain("wp-sitemap-posts-page-1.xml");
  expect(xml).not.toContain("wp-sitemap-users-");
});
