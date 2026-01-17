# FlowForge Documentation (gh-pages)

This branch contains the built documentation artifacts for FlowForge.

## Structure

- `/` - Latest version (currently v4.x)
- `/v3/` - Version 3.x documentation
- `/v2/` - Version 2.x documentation
- `versions.json` - Version metadata for the version switcher

## How It Works

1. Each version branch (4.x, 3.x, 2.x) has its own `/docs/` source
2. When docs change in a branch, GitHub Actions builds only that version
3. The built output is pushed to this branch in the appropriate folder
4. GitHub Pages serves this branch directly

## Manual Deployment

To manually trigger a deployment for a specific version:
1. Go to Actions -> Deploy Docs
2. Click "Run workflow"
3. Enter the version branch (e.g., `4.x`)

## Adding a New Version

When releasing a new major version (e.g., 5.x):
1. Update `.github/workflows/deploy-docs.yml` to include the new branch
2. Update `versions.json` in this branch
3. Decide if new version becomes latest (root) or goes to `/v5/`

## DO NOT

- Edit files directly in this branch (they will be overwritten)
- Delete `versions.json`, `.nojekyll`, or `README.md` manually
- Push to this branch without using the workflow

## Troubleshooting

If the site is broken:
1. Check the workflow run logs
2. Verify `versions.json` is valid JSON
3. Ensure `.nojekyll` exists (prevents Jekyll processing)
