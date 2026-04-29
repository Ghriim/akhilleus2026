## Global
- All classes must be final by default (except for DataModel classes) and readonly when possible.
- All PHP files must contain `declare(strict_types=1);`
- if clauses are always explicit and in yoda style (this rule applies to all types of data)
  - `if($variable)`: wrong
  - `if($variable == true)`: wrong
  - `if($variable === true)`: wrong
  - `if(true == $variable)`: wrong
  - `if(true === $variable)`: good
- All classes must have suffix linked to the type of class:
  - `DataModel` for `/DTO/DataModel`
  - `Repository` for Repository
  - `UseCase` for UseCase
  - `Validator` for Validator

## Mock data
- Fixtures are generated using the FixtureBundle of Symfony.
- They are created in Infrastructure/DataFixtures.

## Database
- Generate an HTML5 file of the database schema and update it every time the schema is changed.
- Store it in `specifications/database-schema.html`

## Conventions for Domain
- No namespace from the outside of Domain can be used in Domain, three exceptions are:
  - `use Doctrine\DBAL\Types\Types` can only be found the `Domain\DTO\DataModel\{SubDomain}`
  - `use Doctrine\ORM\Mapping as ORM` can only be found in `Domain\DTO\DataModel\{SubDomain}`
  - `UserDataModel` can have namespace from `Symfony\Component\Security`
  - `\Exception` : can only be found in `Domain\Exception`

### DTO
- DTOs have public properties and no getters and setters
- DTOs must extend the proper interface
- We have 3 types of DTO:
  - DataInput: defines the entry parameters used for Controllers and Commands
  - DataOuput: defines the exit format produced for Controllers and Commands
  - DataModel: defines the format of a table database as well as the format of external API endpoints.

#### DataModel
- Doctrine entities are called DataModel and use that suffix.
- DataModels require properties createdAt and updatedAt (managed automatically in the AbstractBaseMysqlPersister)

### Registry
- All constants linked to a DTO are store in an interface called Registry.
    - for example: the list of allowed status for Workout would be in Domain/Registry/Workout/WorkoutStatusRegistry.php

## Conventions for Infrastructure
- Only Repository can reference namespace from Domain (DataModel and Gateway/Provider)
- Only Controllers and Command can reference namespace from UseCase

### Repositories
- Repositories implement a Gateway (one repository = one gateway), an interface defined in Domain/Gateway/Provider.
- This gateway is injected instead of the repository everytime we need it.
- The mapping between gateway and repository does not need to be defined in services.yaml as it's a one to one relationship.
- The name of the gateway look like this for a Workout entity WorkoutProviderGateway.
- When querying entities, never use the generic method provided by Doctrine (like find, findOneBy etc) but created a dedicated one
in the repository using the context as name (for example findOneForWorkoutDetails).
- Never rely on lazyloading to prevent performance issue.

### Persister
- Every entity as dedicated persister for create / update / delete operation.
- This persister extends AbstractBaseMysqlPersister and handle the generic operation.
- Persister implement a Gateway (one repository = one gateway), an interface defined in Domain/Gateway/Persister.
- The mapping between gateway and persister does not need to be defined in services.yaml as it's a one to one relationship.
- The name of the gateway look like this for a Workout entity WorkoutPersisterGateway.
- It will manage the createdAt and updatedAt using the ClockInterface.
- In the dedicated persister we will move all operation triggered post create / update / delete.

## Conventions for UseCases
- UseCase are final
- UseCase have only one public method: `execute`
- UseCase only receive DataInputInterface as a parameter
- UseCase only return DataOutputInterface or an array of DataOutputInterface
- UseCase implement the `UseCaseInterface`
- UseCase extends one of the following classes:
  - `AbstractPublicUseCase` : constructor will inject DomainValidatorInterface,
  - `AbstractLoggedUserUseCase`  : constructor will inject AbstractLoggedUserValidator


