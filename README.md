# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/trinityrank/geo-location.svg?style=flat-square)](https://packagist.org/packages/trinityrank/geo-location)
[![Total Downloads](https://img.shields.io/packagist/dt/trinityrank/geo-location.svg?style=flat-square)](https://packagist.org/packages/trinityrank/geo-location)

Choose to show or hide Operaters by choosing the countries from list.

## Installation

### Step 1: Install package

To get started with Laravel Geo Location, use Composer command to add the package to your composer.json project's dependencies:

```shell
    composer require trinityrank/nova-resource-copy
```

### Step 2: Migration

- You need to import class in Nova ressource

```shell
    use Trinityrank\LaravelNovaResourceCopy\NovaResourceCopy;
```

- And then you need add actions function in Nova ressource

```shell
    public function actions(Request $request)
    {
        return [
            new NovaResourceCopy
        ];
    }
```