## Installation

### Step 1: Install package

To get started with Laravel Geo Location, use Composer command to add the package to your composer.json project's dependencies:

```shell
    composer require trinityrank/nova-resource-copy
```

### Step 2: Configuration

- You need to import class in Nova ressource

```shell
    use Trinityrank\LaravelNovaResourceCopy\NovaResourceCopy;
```

- And then you need add actions function in Nova ressource

```shell
    public function actions(Request $request)
    {
        return [
            new NovaResourceCopy([
                ])
        ];
    }
```

- Define the columns you want to add copy 
- Columns title, slug and status have default copy values

```shell
    public function actions(Request $request)
    {
        return [
            new NovaResourceCopy([
                ['name', 'title']
            ]) 
        ];
    }
```

- If you want to copy relationships which are related to the model

```shell
    public function actions(Request $request)
    {
        return [
            new NovaResourceCopy([
                [],
                [['categoriables','categoriable']]
            ]) 
        ];
    }
```

- Example

```shell
    public function actions(Request $request)
    {
        return [
            new NovaResourceCopy([
                ['name', 'title'], 
                [['categoriables','categoriable'], ['seos','seoable'], ['job_tag', job]]
            ]) 
        ];
    }
```

