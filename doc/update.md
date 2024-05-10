# Updating Biblioteca
1. Pull the latest changes from the repository
2. Run `composer install`
3. Run `bin/console doctrine:migrations:migrate`
4. Run `npm i`
5. Run `npm run build`
6. Run `bin/console cache:clear`
