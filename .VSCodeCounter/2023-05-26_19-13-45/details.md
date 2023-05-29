# Details

Date : 2023-05-26 19:13:45

Directory c:\\Users\\juanj\\OneDrive\\Documentos\\whis-core

Total : 77 files,  2731 codes, 705 comments, 717 blanks, all 4153 lines

[Summary](results.md) / Details / [Diff Summary](diff.md) / [Diff Details](diff-details.md)

## Files
| filename | language | code | comment | blank | total |
| :--- | :--- | ---: | ---: | ---: | ---: |
| [composer.json](/composer.json) | JSON | 47 | 0 | 1 | 48 |
| [src/App.php](/src/App.php) | PHP | 110 | 40 | 24 | 174 |
| [src/Auth/Auth.php](/src/Auth/Auth.php) | PHP | 23 | 0 | 7 | 30 |
| [src/Auth/Authenticatable.php](/src/Auth/Authenticatable.php) | PHP | 19 | 0 | 5 | 24 |
| [src/Auth/Authenticators/Authenticator.php](/src/Auth/Authenticators/Authenticator.php) | PHP | 10 | 0 | 4 | 14 |
| [src/Auth/Authenticators/SessionAuthenticator.php](/src/Auth/Authenticators/SessionAuthenticator.php) | PHP | 18 | 1 | 7 | 26 |
| [src/Cli/Cli.php](/src/Cli/Cli.php) | PHP | 105 | 0 | 9 | 114 |
| [src/Cli/Commands/MakeController.php](/src/Cli/Commands/MakeController.php) | PHP | 25 | 0 | 8 | 33 |
| [src/Cli/Commands/MakeMiddleware.php](/src/Cli/Commands/MakeMiddleware.php) | PHP | 26 | 0 | 8 | 34 |
| [src/Cli/Commands/MakeMigration.php](/src/Cli/Commands/MakeMigration.php) | PHP | 24 | 0 | 8 | 32 |
| [src/Cli/Commands/MakeModel.php](/src/Cli/Commands/MakeModel.php) | PHP | 33 | 0 | 9 | 42 |
| [src/Cli/Commands/Migrate.php](/src/Cli/Commands/Migrate.php) | PHP | 24 | 0 | 8 | 32 |
| [src/Cli/Commands/MigrationRollback.php](/src/Cli/Commands/MigrationRollback.php) | PHP | 29 | 0 | 9 | 38 |
| [src/Cli/Commands/Serve.php](/src/Cli/Commands/Serve.php) | PHP | 24 | 0 | 9 | 33 |
| [src/Config/Config.php](/src/Config/Config.php) | PHP | 7 | 22 | 3 | 32 |
| [src/Container/Container.php](/src/Container/Container.php) | PHP | 21 | 0 | 5 | 26 |
| [src/Container/DependencyInjection.php](/src/Container/DependencyInjection.php) | PHP | 76 | 9 | 18 | 103 |
| [src/Cryptic/Bcryptic.php](/src/Cryptic/Bcryptic.php) | PHP | 13 | 0 | 4 | 17 |
| [src/Cryptic/Hasher.php](/src/Cryptic/Hasher.php) | PHP | 7 | 0 | 3 | 10 |
| [src/Database/DB.php](/src/Database/DB.php) | PHP | 10 | 0 | 4 | 14 |
| [src/Database/Drivers/DatabaseDriver.php](/src/Database/Drivers/DatabaseDriver.php) | PHP | 16 | 0 | 6 | 22 |
| [src/Database/Drivers/PdoDriver.php](/src/Database/Drivers/PdoDriver.php) | PHP | 39 | 3 | 9 | 51 |
| [src/Database/Migrations/Migration.php](/src/Database/Migrations/Migration.php) | PHP | 7 | 0 | 4 | 11 |
| [src/Database/Migrations/Migrator.php](/src/Database/Migrations/Migrator.php) | PHP | 35 | 70 | 7 | 112 |
| [src/Database/Model.php](/src/Database/Model.php) | PHP | 243 | 37 | 62 | 342 |
| [src/Exceptions/DatabaseException.php](/src/Exceptions/DatabaseException.php) | PHP | 5 | 0 | 4 | 9 |
| [src/Exceptions/Error.php](/src/Exceptions/Error.php) | PHP | 35 | 31 | 8 | 74 |
| [src/Exceptions/HttpNotFoundException.php](/src/Exceptions/HttpNotFoundException.php) | PHP | 6 | 1 | 4 | 11 |
| [src/Exceptions/NoFillableAttributesError.php](/src/Exceptions/NoFillableAttributesError.php) | PHP | 6 | 0 | 5 | 11 |
| [src/Exceptions/WhisException.php](/src/Exceptions/WhisException.php) | PHP | 6 | 0 | 4 | 10 |
| [src/Helpers/app.php](/src/Helpers/app.php) | PHP | 21 | 0 | 6 | 27 |
| [src/Helpers/auth.php](/src/Helpers/auth.php) | PHP | 9 | 0 | 3 | 12 |
| [src/Helpers/files.php](/src/Helpers/files.php) | PHP | 26 | 0 | 1 | 27 |
| [src/Helpers/http.php](/src/Helpers/http.php) | PHP | 29 | 14 | 11 | 54 |
| [src/Helpers/session.php](/src/Helpers/session.php) | PHP | 19 | 0 | 7 | 26 |
| [src/Helpers/string.php](/src/Helpers/string.php) | PHP | 33 | 0 | 4 | 37 |
| [src/Http/Controller.php](/src/Http/Controller.php) | PHP | 13 | 10 | 5 | 28 |
| [src/Http/HttpMethod.php](/src/Http/HttpMethod.php) | PHP | 10 | 0 | 3 | 13 |
| [src/Http/Middleware.php](/src/Http/Middleware.php) | PHP | 7 | 7 | 4 | 18 |
| [src/Http/Request.php](/src/Http/Request.php) | PHP | 116 | 108 | 30 | 254 |
| [src/Http/Response.php](/src/Http/Response.php) | PHP | 91 | 82 | 24 | 197 |
| [src/Providers/AuthenticationServiceProvider.php](/src/Providers/AuthenticationServiceProvider.php) | PHP | 12 | 0 | 4 | 16 |
| [src/Providers/DatabaseDriverServiceProvider.php](/src/Providers/DatabaseDriverServiceProvider.php) | PHP | 13 | 0 | 3 | 16 |
| [src/Providers/FileStorageDriverServiceProvider.php](/src/Providers/FileStorageDriverServiceProvider.php) | PHP | 20 | 0 | 4 | 24 |
| [src/Providers/HasherServiceProvider.php](/src/Providers/HasherServiceProvider.php) | PHP | 13 | 0 | 5 | 18 |
| [src/Providers/ServerServiceProvider.php](/src/Providers/ServerServiceProvider.php) | PHP | 9 | 0 | 3 | 12 |
| [src/Providers/ServiceProvider.php](/src/Providers/ServiceProvider.php) | PHP | 6 | 0 | 3 | 9 |
| [src/Providers/SessionStorageServiceProvider.php](/src/Providers/SessionStorageServiceProvider.php) | PHP | 13 | 0 | 3 | 16 |
| [src/Providers/ViewServiceProvider.php](/src/Providers/ViewServiceProvider.php) | PHP | 12 | 0 | 4 | 16 |
| [src/Routing/Route.php](/src/Routing/Route.php) | PHP | 85 | 73 | 29 | 187 |
| [src/Routing/Router.php](/src/Routing/Router.php) | PHP | 95 | 75 | 29 | 199 |
| [src/Server/PhpNativeServer.php](/src/Server/PhpNativeServer.php) | PHP | 89 | 22 | 22 | 133 |
| [src/Server/Server.php](/src/Server/Server.php) | PHP | 9 | 11 | 6 | 26 |
| [src/Session/PhpNativeSessionStorage.php](/src/Session/PhpNativeSessionStorage.php) | PHP | 40 | 0 | 11 | 51 |
| [src/Session/Session.php](/src/Session/Session.php) | PHP | 61 | 0 | 14 | 75 |
| [src/Session/SessionStorage.php](/src/Session/SessionStorage.php) | PHP | 13 | 0 | 10 | 23 |
| [src/Storage/Drivers/DiskFileStorage.php](/src/Storage/Drivers/DiskFileStorage.php) | PHP | 47 | 0 | 14 | 61 |
| [src/Storage/Drivers/FileStorageDriver.php](/src/Storage/Drivers/FileStorageDriver.php) | PHP | 7 | 0 | 5 | 12 |
| [src/Storage/File.php](/src/Storage/File.php) | PHP | 45 | 22 | 11 | 78 |
| [src/Storage/FileResponder.php](/src/Storage/FileResponder.php) | PHP | 118 | 0 | 28 | 146 |
| [src/Storage/Storage.php](/src/Storage/Storage.php) | PHP | 45 | 13 | 10 | 68 |
| [src/Validation/Exceptions/RuleParseException.php](/src/Validation/Exceptions/RuleParseException.php) | PHP | 6 | 0 | 4 | 10 |
| [src/Validation/Exceptions/UnknownRuleException.php](/src/Validation/Exceptions/UnknownRuleException.php) | PHP | 6 | 0 | 5 | 11 |
| [src/Validation/Exceptions/ValidationException.php](/src/Validation/Exceptions/ValidationException.php) | PHP | 15 | 3 | 5 | 23 |
| [src/Validation/Rule.php](/src/Validation/Rule.php) | PHP | 104 | 1 | 23 | 128 |
| [src/Validation/Rules/EmailRule.php](/src/Validation/Rules/EmailRule.php) | PHP | 17 | 11 | 10 | 38 |
| [src/Validation/Rules/FiletypeRule.php](/src/Validation/Rules/FiletypeRule.php) | PHP | 38 | 0 | 6 | 44 |
| [src/Validation/Rules/LessThanRule.php](/src/Validation/Rules/LessThanRule.php) | PHP | 22 | 0 | 6 | 28 |
| [src/Validation/Rules/NumberRule.php](/src/Validation/Rules/NumberRule.php) | PHP | 16 | 0 | 4 | 20 |
| [src/Validation/Rules/RequiredRule.php](/src/Validation/Rules/RequiredRule.php) | PHP | 16 | 0 | 3 | 19 |
| [src/Validation/Rules/RequiredWhenRule.php](/src/Validation/Rules/RequiredWhenRule.php) | PHP | 34 | 7 | 8 | 49 |
| [src/Validation/Rules/RequiredWithRule.php](/src/Validation/Rules/RequiredWithRule.php) | PHP | 21 | 0 | 6 | 27 |
| [src/Validation/Rules/ValidationRule.php](/src/Validation/Rules/ValidationRule.php) | PHP | 7 | 0 | 4 | 11 |
| [src/Validation/Validator.php](/src/Validation/Validator.php) | PHP | 51 | 11 | 9 | 71 |
| [src/View/RinzlerEngine.php](/src/View/RinzlerEngine.php) | PHP | 35 | 7 | 13 | 55 |
| [src/View/StencilEngine.php](/src/View/StencilEngine.php) | PHP | 162 | 7 | 26 | 195 |
| [src/View/ViewEngine.php](/src/View/ViewEngine.php) | PHP | 6 | 7 | 3 | 16 |

[Summary](results.md) / Details / [Diff Summary](diff.md) / [Diff Details](diff-details.md)