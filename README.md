```
php artisan vendor:publish --provider="ThomasFielding\Version\VersionServiceProvider" --tag="template"
```

## Auth and User Metadata
You can setup your user metadata by either referencing the packages namespaced user model `ThomasFielding\Kiwi\Models\User` which extends the default Laravel User model, or alternatively manually add the following functions to your User model:

```
/**
 * Get the user metadata relationships
 *
 * @return \ThomasFielding\Kiwi\Models\UserMetadata
 */
public function metadata()
{
    return $this->hasMany(UserMetadata::class);
}
```

> Make sure to reference the UserMetadata model found under the `ThomasFielding\Kiwi\Models\UserMetadata` namespace in your User model if you are not using the packages provided model.