# Skeleton website (template)

Starter package for building websites on top of K2D.CZ CMS

## Installation
- Create your own repository from this one by clicking **Use this template** green button
- Install **Composer** dependencies - `$ composer install`
- Install **NPM** dependencies - `$ npm install`
- Build front-end **assets**:
	- Development: `npm run start` or `npm run watch`
	- Production: `npm run build`
- **Create file** `app/config/server/local.neon` from `app/config/server/local.template.neon` and **configure database and dbal**
- **Run** `$ bin/console migration:continue`
- **Enjoy**

## Github Actions (phpstan and codesniffer)
- rename `.github/workflows-inactive` to `.github/workflows`
- ensure in `.github/workflows/ci.yaml` option `on > push > branches` is set to correct branch
- setup Github secret `SSH_PRIVATE_KEY` with your Github private key, so the build can install private packages
