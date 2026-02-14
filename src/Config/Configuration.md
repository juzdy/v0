Implement Configuration class that loads and resolves configuration data from php files which returns an array in a specified directory. 
The configuration keys should be accessible using dot notation (e.g., 'database.host' for config/database.php returning ['host' => 'localhost']).
The functionality should support nested configuration and allow for dynamic resolution of values using resolvers.
When loading subdirectories, the configuration should be nested accordingly (e.g., config/app.php returning ['app' => [...]] and config/app/cache.php returning ['app' => ['cache' => [...]]]).
More high-level directory structure should override the more specific one (e.g., config/app.php should override config/app/cache.php for keys that exist in both).
Dynamic resolution (lazy) should be supported, allowing for values to be defined as resolvers;
Resolution logic should be easy extendable in feature for more complex resolution rules, such as referencing other configuration values or environment variables.
After loading all configuration files, the class should resolve all values that are defined as resolvers, allowing for dynamic computation of configuration values based on other configuration values or external factors.
Fit SOLIT, DRY and other best practices in the implementation, ensuring that the code is maintainable, extensible, and efficient.
You may create additional files and directories as needed to support the implementation, such as separate classes for resolvers and interpolators, and a manager class to handle the resolution process.

Supported resolution rules:
Values:
- "@env(VAR_NAME)" should resolve to the value of the specified environment variable.
- "@(path.to.value)" should resolve to the value at the specified path in the configuration. f.e. "@(root)/pub/uploads" should resolve to the value of 'root' concatenated with "/pub/uploads".
Keys:
- ["@extends" => [path.to, ...]] should allow for extending other configuration paths, merging their values with the current one. The current configuration should take precedence over the extended ones in case of conflicts.
    "path.to" may be relative if use in way "@extends" => ["./relative.path", "../sibling.path"].
