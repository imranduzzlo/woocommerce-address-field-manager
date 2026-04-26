# WooCommerce Address Field Manager - GitHub Deployment Script
# This script pushes the plugin to GitHub and creates a new release

param(
    [string]$Version = "1.0.0",
    [string]$Message = "Initial release - WooCommerce Address Field Manager for Bangladesh",
    [switch]$SkipTests = $false
)

# Configuration
$RepoUrl = "https://github.com/imranduzzlo/woocommerce-address-field-manager.git"
$PluginName = "WooCommerce Address Field Manager"
$MainBranch = "main"

# Colors for output
function Write-Success { Write-Host $args -ForegroundColor Green }
function Write-Info { Write-Host $args -ForegroundColor Cyan }
function Write-Warning { Write-Host $args -ForegroundColor Yellow }
function Write-Error { Write-Host $args -ForegroundColor Red }

# Banner
Write-Host ""
Write-Host "========================================" -ForegroundColor Magenta
Write-Host "  $PluginName" -ForegroundColor Magenta
Write-Host "  GitHub Deployment Script" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta
Write-Host ""

# Check if git is installed
Write-Info "Checking for Git installation..."
try {
    $gitVersion = git --version
    Write-Success "✓ Git found: $gitVersion"
} catch {
    Write-Error "✗ Git is not installed or not in PATH"
    Write-Error "Please install Git from https://git-scm.com/"
    exit 1
}

# Check if we're in a git repository
Write-Info "Checking Git repository status..."
if (-not (Test-Path ".git")) {
    Write-Warning "Not a Git repository. Initializing..."
    git init
    git remote add origin $RepoUrl
    Write-Success "✓ Git repository initialized"
} else {
    Write-Success "✓ Git repository found"
}

# Check for uncommitted changes
Write-Info "Checking for uncommitted changes..."
$status = git status --porcelain
if ($status) {
    Write-Warning "Found uncommitted changes:"
    Write-Host $status
    Write-Host ""
} else {
    Write-Success "✓ Working directory is clean"
}

# Update version in main plugin file
Write-Info "Updating version to $Version..."
$pluginFile = "woocommerce-address-field-manager.php"
if (Test-Path $pluginFile) {
    $content = Get-Content $pluginFile -Raw
    $content = $content -replace "Version:\s*[\d\.]+", "Version: $Version"
    $content = $content -replace "define\(\s*'WAFM_PLUGIN_VERSION',\s*'[\d\.]+'\s*\)", "define( 'WAFM_PLUGIN_VERSION', '$Version' )"
    Set-Content $pluginFile $content -NoNewline
    Write-Success "✓ Version updated in $pluginFile"
} else {
    Write-Error "✗ Plugin file not found: $pluginFile"
    exit 1
}

# Update CHANGELOG.md
Write-Info "Updating CHANGELOG.md..."
$changelogFile = "CHANGELOG.md"
$date = Get-Date -Format "yyyy-MM-dd"
$changelogEntry = @"

## [$Version] - $date

### Changes
$Message

"@

if (Test-Path $changelogFile) {
    $changelog = Get-Content $changelogFile -Raw
    # Insert new entry after the first heading
    $changelog = $changelog -replace "(# Changelog\s*\n)", "`$1$changelogEntry"
    Set-Content $changelogFile $changelog -NoNewline
    Write-Success "✓ CHANGELOG.md updated"
} else {
    Write-Warning "CHANGELOG.md not found, skipping..."
}

# Stage all changes
Write-Info "Staging changes..."
git add .
Write-Success "✓ Changes staged"

# Commit changes
Write-Info "Committing changes..."
$commitMessage = "Release v$Version - $Message"
git commit -m $commitMessage
if ($LASTEXITCODE -eq 0) {
    Write-Success "✓ Changes committed: $commitMessage"
} else {
    Write-Warning "No changes to commit or commit failed"
}

# Check remote
Write-Info "Checking remote repository..."
$remotes = git remote -v
if ($remotes -match "origin") {
    Write-Success "✓ Remote 'origin' configured"
} else {
    Write-Info "Adding remote 'origin'..."
    git remote add origin $RepoUrl
    Write-Success "✓ Remote added"
}

# Push to GitHub
Write-Info "Pushing to GitHub ($MainBranch branch)..."
Write-Warning "You may be prompted for GitHub credentials..."
git push -u origin $MainBranch
if ($LASTEXITCODE -eq 0) {
    Write-Success "✓ Successfully pushed to GitHub"
} else {
    Write-Error "✗ Failed to push to GitHub"
    Write-Error "Please check your credentials and repository access"
    exit 1
}

# Create and push tag
Write-Info "Creating release tag v$Version..."
git tag -a "v$Version" -m "Release version $Version"
git push origin "v$Version"
if ($LASTEXITCODE -eq 0) {
    Write-Success "✓ Tag v$Version created and pushed"
} else {
    Write-Error "✗ Failed to create/push tag"
    exit 1
}

# Create GitHub Release (requires GitHub CLI)
Write-Info "Checking for GitHub CLI..."
try {
    $ghVersion = gh --version
    Write-Success "✓ GitHub CLI found"
    
    Write-Info "Creating GitHub Release..."
    $releaseNotes = @"
## What's New in v$Version

$Message

### Installation
1. Download the plugin ZIP file from the assets below
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate

### Changelog
See [CHANGELOG.md](https://github.com/imranduzzlo/woocommerce-address-field-manager/blob/main/CHANGELOG.md) for full details.
"@
    
    # Create release
    gh release create "v$Version" `
        --title "v$Version" `
        --notes $releaseNotes `
        --repo "imranduzzlo/woocommerce-address-field-manager"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Success "✓ GitHub Release created successfully"
    } else {
        Write-Warning "Failed to create GitHub Release via CLI"
        Write-Info "You can create it manually at:"
        Write-Info "https://github.com/imranduzzlo/woocommerce-address-field-manager/releases/new?tag=v$Version"
    }
} catch {
    Write-Warning "GitHub CLI not found. Skipping release creation."
    Write-Info "Install GitHub CLI from: https://cli.github.com/"
    Write-Info "Or create release manually at:"
    Write-Info "https://github.com/imranduzzlo/woocommerce-address-field-manager/releases/new?tag=v$Version"
}

# Summary
Write-Host ""
Write-Host "========================================" -ForegroundColor Magenta
Write-Host "  Deployment Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Magenta
Write-Host ""
Write-Success "Version: $Version"
Write-Success "Repository: $RepoUrl"
Write-Success "Tag: v$Version"
Write-Host ""
Write-Info "Next Steps:"
Write-Host "  1. Visit: https://github.com/imranduzzlo/woocommerce-address-field-manager/releases"
Write-Host "  2. Verify the release was created"
Write-Host "  3. Add release assets (ZIP file) if needed"
Write-Host ""

# Create plugin ZIP file
Write-Info "Creating plugin ZIP file..."
$zipName = "woocommerce-address-field-manager-v$Version.zip"
$tempDir = "woocommerce-address-field-manager"

# Create temp directory structure
if (Test-Path $tempDir) {
    Remove-Item $tempDir -Recurse -Force
}
New-Item -ItemType Directory -Path $tempDir | Out-Null

# Copy plugin files (exclude git, scripts, and development files)
$excludePatterns = @('.git', '.gitignore', '*.ps1', 'node_modules', '.vscode', '.idea', 'FIXES-APPLIED.md')
Get-ChildItem -Path . -Exclude $excludePatterns | ForEach-Object {
    if ($_.Name -notin $excludePatterns) {
        Copy-Item $_.FullName -Destination $tempDir -Recurse -Force
    }
}

# Create ZIP
if (Test-Path $zipName) {
    Remove-Item $zipName -Force
}
Compress-Archive -Path $tempDir -DestinationPath $zipName -Force
Remove-Item $tempDir -Recurse -Force

Write-Success "✓ Plugin ZIP created: $zipName"
Write-Info "Upload this ZIP to the GitHub release if needed"
Write-Host ""
