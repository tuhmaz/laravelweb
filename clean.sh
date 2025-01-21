#!/bin/bash

echo "🧹 Cleaning up unnecessary files..."

# حذف مجلدات التطوير
rm -rf node_modules
rm -rf .git
rm -rf tests
rm -rf vendor

# حذف ملفات التكوين والتطوير
rm -f .editorconfig
rm -f .eslintignore
rm -f .eslintrc.json
rm -f .gitattributes
rm -f .gitignore
rm -f .prettierignore
rm -f .prettierrc.json
rm -f phpunit.xml
rm -f README.md
rm -f yarn.lock
rm -f package-lock.json
rm -f docker-compose.yml
rm -rf docker
rm -rf docs

# حذف ملفات التطوير الأخرى
find . -name "*.log" -type f -delete
find . -name "*.map" -type f -delete
find . -name "*.test.js" -type f -delete
find . -name "*.test.php" -type f -delete

echo "✨ Cleanup complete!"
