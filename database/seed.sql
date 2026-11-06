-- ============================================================================
-- MULTI-USER URL SHORTENER PLATFORM (SLink)
-- Initial Seed Data
-- ============================================================================
-- Default Initial Accounts (Non-trivial production-safe credentials):
-- 1. Superuser:
--    Email   : admin@s.domain.com
--    Password: SLink#Admin!92xQv
--
-- 2. Standard User (Marketing):
--    Email   : nadia@s.domain.com
--    Password: SLink#User!48mKp
--
-- 3. Standard User (Product):
--    Email   : rafi@s.domain.com
--    Password: SLink#User!48mKp
-- ============================================================================

USE `slink_db`;

-- 1. Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'SLink Enterprise'),
('app_tagline', 'Centralized Multi-User URL Shortener & Analytics'),
('shortlink_domain', ''),
('default_code_length', '6'),
('redirect_status_code', '302'),
('track_bot_clicks', '1'),
('allow_custom_alias', '1'),
('max_links_per_user', '0')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 2. Users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `last_login_at`, `created_at`) VALUES
(1, 'System Administrator', 'admin@s.domain.com', '$2y$10$fjYmQEatR./l6pKIIq5ftuIuOzEFnTR1R9Vb8yPiQGBTw7PmaxiCu', 'superuser', 'active', NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 30 DAY),
(2, 'Nadia Pratama', 'nadia@s.domain.com', '$2y$10$CxOX6UpT9KPtbfRXWWf2pOz10jSoJZAEfBmfuTuKUPlUjMztkrmgu', 'user', 'active', NOW() - INTERVAL 3 HOUR, NOW() - INTERVAL 20 DAY),
(3, 'Rafi Santoso', 'rafi@s.domain.com', '$2y$10$CxOX6UpT9KPtbfRXWWf2pOz10jSoJZAEfBmfuTuKUPlUjMztkrmgu', 'user', 'active', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 14 DAY),
(4, 'Dimas Wijaya', 'dimas@s.domain.com', '$2y$10$CxOX6UpT9KPtbfRXWWf2pOz10jSoJZAEfBmfuTuKUPlUjMztkrmgu', 'user', 'inactive', NULL, NOW() - INTERVAL 7 DAY);

-- 3. Shortlinks
INSERT INTO `shortlinks` (`id`, `user_id`, `short_code`, `destination_url`, `title`, `description`, `status`, `expires_at`, `max_clicks`, `total_clicks`, `human_clicks`, `bot_clicks`, `created_at`) VALUES
(1, 2, 'promo2026', 'https://example.com/campaigns/year-end-enterprise-2026?utm_source=newsletter&utm_medium=email&utm_campaign=yearend_2026', 'Year-End Campaign 2026', 'Primary shortlink for Q4 enterprise newsletter campaign.', 'active', NOW() + INTERVAL 90 DAY, 5000, 12, 10, 2, NOW() - INTERVAL 10 DAY),
(2, 2, 'aB92xK', 'https://example.com/products/cloud-analytics-suite', 'Cloud Analytics Product Page', 'Product launch link shared across social channels.', 'active', NULL, NULL, 7, 6, 1, NOW() - INTERVAL 7 DAY),
(3, 3, 'devDocs', 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Redirections', 'MDN HTTP Redirections Guide', 'Engineering reference documentation link.', 'active', NULL, 500, 5, 4, 1, NOW() - INTERVAL 5 DAY),
(4, 1, 'statusPage', 'https://example.com/system-status', 'Platform Status Page', 'Official status page link maintained by Superuser.', 'active', NULL, NULL, 4, 3, 1, NOW() - INTERVAL 12 DAY),
(5, 2, 'flashSale', 'https://example.com/deals/flash-sale-expired', 'Expired Flash Sale Link', 'Campaign link that has already passed its expiration timestamp.', 'active', NOW() - INTERVAL 2 DAY, 100, 2, 2, 0, NOW() - INTERVAL 15 DAY),
(6, 3, 'legacyApp', 'https://example.com/legacy-portal', 'Deactivated Legacy Portal', 'Inactive link kept for historical analytics.', 'inactive', NULL, NULL, 1, 1, 0, NOW() - INTERVAL 9 DAY);

-- 4. Clicks (Human & Bot clicks distributed over the last 7 days)
INSERT INTO `clicks` (`shortlink_id`, `ip_address`, `user_agent`, `browser`, `os`, `device_type`, `country`, `city`, `referrer`, `referrer_domain`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `is_bot`, `clicked_at`) VALUES
(1, '103.144.12.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'Windows', 'Desktop', 'Indonesia', 'Jakarta', 'https://www.linkedin.com/feed/', 'linkedin.com', 'linkedin', 'social', 'yearend_2026', 'b2b', 'hero_banner', 0, NOW() - INTERVAL 6 DAY),
(1, '114.122.88.21', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1', 'Safari', 'iOS', 'Mobile', 'Indonesia', 'Surabaya', 'https://t.co/xyz123', 't.co', 'twitter', 'social', 'yearend_2026', NULL, 'tweet_1', 0, NOW() - INTERVAL 5 DAY),
(1, '182.253.40.19', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'macOS', 'Desktop', 'Indonesia', 'Bandung', 'https://mail.google.com/', 'mail.google.com', 'newsletter', 'email', 'yearend_2026', NULL, 'cta_button', 0, NOW() - INTERVAL 5 DAY),
(1, '66.249.66.1', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'Bot / Crawler', 'Bot', 'Bot', 'United States', 'Mountain View', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 1, NOW() - INTERVAL 4 DAY),
(1, '103.28.14.92', 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/122.0.0.0 Mobile Safari/537.36', 'Chrome', 'Android', 'Mobile', 'Singapore', 'Singapore', 'https://www.instagram.com/', 'instagram.com', 'instagram', 'social', 'yearend_2026', NULL, 'bio_link', 0, NOW() - INTERVAL 4 DAY),
(1, '36.72.110.44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0', 'Firefox', 'Windows', 'Desktop', 'Indonesia', 'Jakarta', 'https://www.google.com/', 'google.com', 'google', 'cpc', 'yearend_2026', 'enterprise SaaS', 'ad_group_a', 0, NOW() - INTERVAL 3 DAY),
(1, '139.192.45.10', 'WhatsApp/2.24.4.76 A', 'Bot / Crawler', 'Bot', 'Bot', 'Indonesia', 'Jakarta', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 1, NOW() - INTERVAL 3 DAY),
(1, '114.124.190.5', 'Mozilla/5.0 (iPad; CPU OS 17_3 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1', 'Safari', 'iOS', 'Tablet', 'Indonesia', 'Yogyakarta', 'https://www.linkedin.com/', 'linkedin.com', 'linkedin', 'social', 'yearend_2026', NULL, 'post_2', 0, NOW() - INTERVAL 2 DAY),
(1, '103.10.66.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Edg/122.0.0.0', 'Edge', 'Windows', 'Desktop', 'Malaysia', 'Kuala Lumpur', NULL, 'Direct', 'newsletter', 'email', 'yearend_2026', NULL, 'footer_link', 0, NOW() - INTERVAL 2 DAY),
(1, '103.144.12.55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'Windows', 'Desktop', 'Indonesia', 'Jakarta', 'https://news.ycombinator.com/', 'news.ycombinator.com', 'hn', 'referral', 'yearend_2026', NULL, NULL, 0, NOW() - INTERVAL 1 DAY),
(1, '180.242.11.77', 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 Chrome/122.0.0.0 Mobile Safari/537.36', 'Chrome', 'Android', 'Mobile', 'Indonesia', 'Medan', 'https://t.co/abc987', 't.co', 'twitter', 'social', 'yearend_2026', NULL, 'thread_top', 0, NOW() - INTERVAL 12 HOUR),
(1, '103.144.12.99', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3) AppleWebKit/605.1.15 Version/17.3 Safari/605.1.15', 'Safari', 'macOS', 'Desktop', 'Indonesia', 'Jakarta', 'https://www.linkedin.com/', 'linkedin.com', 'linkedin', 'social', 'yearend_2026', NULL, 'exec_post', 0, NOW() - INTERVAL 2 HOUR),

(2, '103.144.18.12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'Windows', 'Desktop', 'Indonesia', 'Jakarta', 'https://www.google.com/', 'google.com', 'google', 'organic', 'product_launch', NULL, NULL, 0, NOW() - INTERVAL 6 DAY),
(2, '114.122.10.44', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1', 'Safari', 'iOS', 'Mobile', 'Indonesia', 'Jakarta', 'https://www.instagram.com/', 'instagram.com', 'instagram', 'social', 'product_launch', NULL, 'story_1', 0, NOW() - INTERVAL 4 DAY),
(2, '103.28.14.11', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'Linux', 'Desktop', 'Singapore', 'Singapore', 'https://github.com/', 'github.com', 'github', 'referral', 'product_launch', NULL, 'readme', 0, NOW() - INTERVAL 3 DAY),
(2, '40.77.167.5', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', 'Bot / Crawler', 'Bot', 'Bot', 'United States', 'Redmond', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 1, NOW() - INTERVAL 2 DAY),
(2, '36.72.110.90', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'Windows', 'Desktop', 'Indonesia', 'Semarang', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 0, NOW() - INTERVAL 1 DAY),
(2, '182.253.40.88', 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/121.0.0.0 Mobile Safari/537.36', 'Chrome', 'Android', 'Mobile', 'Indonesia', 'Bandung', 'https://www.facebook.com/', 'facebook.com', 'facebook', 'paid_social', 'product_launch', NULL, 'carousel_ad', 0, NOW() - INTERVAL 8 HOUR),
(2, '103.144.18.99', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'macOS', 'Desktop', 'Indonesia', 'Jakarta', 'https://www.linkedin.com/', 'linkedin.com', 'linkedin', 'social', 'product_launch', NULL, NULL, 0, NOW() - INTERVAL 1 HOUR),

(3, '103.144.20.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:123.0) Gecko/20100101 Firefox/123.0', 'Firefox', 'Linux', 'Desktop', 'Indonesia', 'Jakarta', 'https://github.com/', 'github.com', 'github', 'docs', 'eng_onboarding', NULL, NULL, 0, NOW() - INTERVAL 4 DAY),
(3, '103.144.20.2', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'macOS', 'Desktop', 'Indonesia', 'Jakarta', 'https://slack.com/', 'slack.com', 'slack', 'internal', 'eng_onboarding', NULL, NULL, 0, NOW() - INTERVAL 3 DAY),
(3, '54.236.1.12', 'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)', 'Bot / Crawler', 'Bot', 'Bot', 'United States', 'Ashburn', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 1, NOW() - INTERVAL 3 DAY),
(3, '103.28.14.55', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'Windows', 'Desktop', 'Singapore', 'Singapore', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 0, NOW() - INTERVAL 1 DAY),
(3, '114.122.45.67', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1', 'Safari', 'iOS', 'Mobile', 'Indonesia', 'Surabaya', 'https://t.co/dev99', 't.co', 'twitter', 'social', 'eng_onboarding', NULL, NULL, 0, NOW() - INTERVAL 4 HOUR),

(4, '103.144.12.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'Windows', 'Desktop', 'Indonesia', 'Jakarta', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 0, NOW() - INTERVAL 5 DAY),
(4, '103.144.12.2', 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/122.0.0.0 Mobile Safari/537.36', 'Chrome', 'Android', 'Mobile', 'Indonesia', 'Jakarta', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 0, NOW() - INTERVAL 2 DAY),
(4, '35.190.10.1', 'TelegramBot (like TwitterBot)', 'Bot / Crawler', 'Bot', 'Bot', 'United States', 'Council Bluffs', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 1, NOW() - INTERVAL 2 DAY),
(4, '103.144.12.3', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3) AppleWebKit/605.1.15 Safari/605.1.15', 'Safari', 'macOS', 'Desktop', 'Indonesia', 'Bandung', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 0, NOW() - INTERVAL 6 HOUR),

(5, '103.144.15.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'Windows', 'Desktop', 'Indonesia', 'Jakarta', 'https://www.instagram.com/', 'instagram.com', 'instagram', 'social', 'flash_sale', NULL, NULL, 0, NOW() - INTERVAL 6 DAY),
(5, '103.144.15.11', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1', 'Safari', 'iOS', 'Mobile', 'Indonesia', 'Jakarta', 'https://www.instagram.com/', 'instagram.com', 'instagram', 'social', 'flash_sale', NULL, NULL, 0, NOW() - INTERVAL 5 DAY),

(6, '103.144.19.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36', 'Chrome', 'Windows', 'Desktop', 'Indonesia', 'Jakarta', NULL, 'Direct', NULL, NULL, NULL, NULL, NULL, 0, NOW() - INTERVAL 7 DAY);

-- 5. Activity Logs
INSERT INTO `activity_logs` (`user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 'system.seed', 'Initialized SLink database with baseline configuration and accounts.', '127.0.0.1', NOW() - INTERVAL 14 DAY),
(1, 'user.create', 'Created user account for Nadia Pratama (nadia@s.domain.com).', '127.0.0.1', NOW() - INTERVAL 12 DAY),
(1, 'user.create', 'Created user account for Rafi Santoso (rafi@s.domain.com).', '127.0.0.1', NOW() - INTERVAL 10 DAY),
(2, 'shortlink.create', 'Created custom shortlink /promo2026 pointing to Year-End Campaign 2026.', '103.144.12.10', NOW() - INTERVAL 10 DAY),
(2, 'shortlink.create', 'Created shortlink /aB92xK pointing to Cloud Analytics Product Page.', '103.144.12.10', NOW() - INTERVAL 7 DAY),
(3, 'shortlink.create', 'Created shortlink /devDocs pointing to MDN HTTP Redirections Guide.', '103.144.20.1', NOW() - INTERVAL 5 DAY),
(1, 'auth.login', 'Superuser logged in successfully.', '127.0.0.1', NOW() - INTERVAL 1 HOUR);

