Here are some aliases that you can add to your `~/.bash_profile` file.

```bash
alias g:c="php artisan generate:controller"
alias g:m="php artisan generate:model"
alias g:v="php artisan generate:view"
alias g:mig-"php artisan generate:migration"
alias g:a="php artisan generate:assets"
alias g:t="php artisan generate:test"
alias g:r="php artisan generate:resource"
```

Now, for example, to generate a new controller and methods, you can simply do:

```bash
g:c admin index edit
```
