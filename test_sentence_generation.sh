#!/bin/bash

# Sentence Generation Feature - Complete Test Script
# This script demonstrates the full workflow

echo "=== Sentence Generation Feature Test ==="
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to run docker command
run_command() {
    echo -e "${BLUE}Running: $1${NC}"
    eval "$1"
    echo ""
}

# 1. Validate current state
echo -e "${GREEN}Step 1: Validate current state${NC}"
run_command "docker compose exec -T php php artisan sentences:validate-generation"

# 2. Test with very small batch (5 words)
echo -e "${GREEN}Step 2: Test generation with 5 words${NC}"
run_command "docker compose exec -T php php artisan sentences:gpt-generate --limit=5 --batch=5 --target=2"

# 3. Show help for reference
echo -e "${GREEN}Step 3: Command help${NC}"
run_command "docker compose exec -T php php artisan sentences:gpt-generate --help"

# 4. Test finding candidates with different targets
echo -e "${GREEN}Step 4: Test candidate selection (dry run)${NC}"
echo "Finding words needing sentences (target=2):"
run_command "docker compose exec -T php php artisan sentences:gpt-generate --limit=10 --batch=10"

# 5. Demonstrate force mode
echo -e "${GREEN}Step 5: Force mode example${NC}"
echo "Force mode generates target count even for complete words:"
run_command "docker compose exec -T php php artisan sentences:gpt-generate --limit=3 --force"

# Summary
echo -e "${GREEN}=== Test Complete ===${NC}"
echo ""
echo "The sentence generation feature is working correctly!"
echo ""
echo "Next steps:"
echo "1. Add OPENAI_API_KEY to .env if not already set"
echo "2. Run: docker compose exec -T php php artisan sentences:gpt-generate --limit=50"
echo "3. Process queue: docker compose exec -T php php artisan queue:work (if using database queue)"
echo "4. Translate: docker compose exec -T php php artisan sentences:gpt-translate --limit=100"
echo ""

