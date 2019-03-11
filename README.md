# AWS Parameters filler

[![Build Status](https://travis-ci.com/keboola/aws-parameter-filler.svg?branch=master)](https://travis-ci.com/keboola/aws-parameter-filler)

Fetches parameters from [AWS SSM](https://docs.aws.amazon.com/systems-manager/latest/userguide/systems-manager-paramstore.html) from a given parameter namespace (prefix) and region. Stores the 
parameters as an .env file.


# Usage
The executable takes thee parmeters:

- destination file
- parameter namespace (starts and ends with slash)
- AWS region

Example:

```
aws-parameter-filler .env /company/my-application/ us-east-1
```

AWS credentials with `GetParametersByPath` and `GetParameter` permissons must be available
in any of the [standard ways](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html).

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/aws-parameter-filler
cd aws-parameter-filler
docker-compose build
```

Use `test-cf-stack.json` to create a testing stack. Create an access key for the generated user. Also [manually create](https://console.aws.amazon.com/systems-manager/parameters/create) SecureString parameter, which cannot be created with cloudformation template: 

```
Name: /keboola/$STACKNAME/aws-parameter-filler/six
Value: SuperSecretValue
Type: SecureString
Description: Parameter filler test - Encrypted parameter
```

Set the following environment variables (or use `.env.template`):

- `AWS_ACCESS_KEY_ID` - From the created access key.
- `AWS_SECRET_ACCESS_KEY` - From the created acces key.
- `TEST_NAMESPACE` - The value of the `Namespace` stack output.
- `TEST_REGION` - The value of the `Region` stack output.

Run the test suite using this command:

```
docker-compose run
```
 
# Composer Integration
The utility is usable with composer. Add the library to your project by running:

```
composer require keboola/aws-parameter-filler
```

Then add the following to your `composer.json`:

```
"scripts": {
	"get-parameters": "aws-parameter-filler .env /my-stack/ us-east-1"
}
```

Running `composer get-parameters` will then download parameters with the specified namespace and store them
in `.env` file of the application root. Environment variables may also be used:

```
"scripts": {
	"get-parameters": "aws-parameter-filler .env /keboola/$KEBOOLA_STACK/runner-sync-api/ $KEBOOLA_STACK_REGION"
}
```
