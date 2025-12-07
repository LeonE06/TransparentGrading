#15 4.054   75/104 [====================>-------]  72%
#15 4.394   85/104 [======================>-----]  81%
#15 4.768   98/104 [==========================>-]  94%
#15 4.933  104/104 [============================] 100%
#15 5.009 Generating optimized autoload files
#15 6.460 84 packages you are using are looking for funding.
#15 6.460 Use the `composer fund` command to find out more!
#15 6.460 
#15 6.460 Run composer recipes at any time to see the status of your Symfony recipes.
#15 6.460 
#15 6.461 Executing script cache:clear [KO]
#15 6.886  [KO]
#15 6.886 Script cache:clear returned with error code 1
#15 6.886 !!  
#15 6.886 !!  In DefinitionErrorExceptionPass.php line 48:
#15 6.886 !!                                                                                 
#15 6.886 !!    Cannot autowire service "App\Service\MicrosoftUserService": argument "$repo  
#15 6.886 !!    " of method "__construct()" has type "App\Repository\MicrosoftUserRepositor  
#15 6.886 !!    y" but this class was not found.                                             
#15 6.886 !!                                                                                 
#15 6.886 !!  
#15 6.886 !!  
#15 6.886 Script @auto-scripts was called via post-install-cmd
#15 ERROR: process "/bin/sh -c COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction" did not complete successfully: exit code: 1
------
 > [prod 2/3] RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction:
6.886 !!  
6.886 !!  In DefinitionErrorExceptionPass.php line 48:
6.886 !!                                                                                 
6.886 !!    Cannot autowire service "App\Service\MicrosoftUserService": argument "$repo  
6.886 !!    " of method "__construct()" has type "App\Repository\MicrosoftUserRepositor  
6.886 !!    y" but this class was not found.                                             
6.886 !!                                                                                 
6.886 !!  
6.886 !!  
6.886 Script @auto-scripts was called via post-install-cmd
------
Dockerfile:47
--------------------
  45 |     
  46 |     # Symfony Abhängigkeiten installieren (ohne dev)
  47 | >>> RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction
  48 |     
  49 |     # Cache & Rechte fixen
--------------------
error: failed to solve: process "/bin/sh -c COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction" did not complete successfully: exit code: 1