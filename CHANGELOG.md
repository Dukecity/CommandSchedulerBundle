### New in Version 6:
- Read Upgrade
- Ensure that the log file reside in the log directory and finish with a ".log" extension
- Add command `scheduler:disable` to disable one or all scheduled commands
- Add support of Symfony 7
- Drop support of Symfony < 6.3

### New in Version 5:
- Add command to disable commands (by name or all). Useful for staging environments
- Drop support of Symfony < 5.4

### New in Version 4:
- API for all functions (in development)
- Event-Handling (preExecution, postExecution). You can subscribe to this [Events](Resources/doc/integrations/events/index.md)
- Monitoring: Optional Notifications with the [Symfony Notifier](https://symfony.com/doc/current/notifier.html) Component. Default: E-Mail
- Refactored Execution of Commands to Services. You can use them now from other Services.
- Handled error in Command Parsing. So there is no 500 Error while parsing commands.
- You CLI-commands for add, remove and list scheduled commands
- Improved UI of command-execution in cli

### Version 3:
- An admin interface to add, edit, enable/disable or delete scheduled commands.
- For each command, you define :
    - name
    - symfony console command (choice based on native `list` command)
    - cron expression (see [Cron format](http://en.wikipedia.org/wiki/Cron#Format) for informations)
    - output file (for `$output->write`)
    - priority
- A new console command `scheduler:execute [--dump] [--no-output]` which will be the single entry point to all commands
- Management of queuing and prioritization between tasks
- Locking system, to stop scheduling a command that has returned an error
- Monitoring with timeout or failed commands (Json URL and command with mailing)
- Translated in french, english, german and spanish
- An [EasyAdmin](https://github.com/EasyCorp/EasyAdminBundle) configuration template available [here](Resources/doc/integrations/easyadmin/index.md)
- **Beta** - Handle commands with a daemon (unix only) if you don't want to use a cronjob