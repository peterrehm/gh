gh - GitHub/Git toolkit
=======================

This tool aims to provide support for common git/GitHub functionality
and evolves from being a simple mergetool to provide handy shortcuts
to frequently used commands.

gh expects an composer.json with a valid name in the git root directory.
If you do not have a composer.json and you are executing GitHub related
commands like the merge command you need to specify the username and
the repository.

gh also expects by convention a remote with the organization name. If such
does not exist gh creates one when a command needs it.


Start with

````
./gh configure
````

to setup your GitHub token.

Afterwards use `./gh` to get an overview over the available functions.
