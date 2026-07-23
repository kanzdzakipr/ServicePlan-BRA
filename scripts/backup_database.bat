@echo off
set PROJECT=C:\laragon\www\asset-manager-laragon-v2
set MYSQLDUMP=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe
set BACKUP=%PROJECT%\backups\asset_manager_%DATE:~-4%%DATE:~4,2%%DATE:~7,2%_%TIME:~0,2%%TIME:~3,2%.sql
set BACKUP=%BACKUP: =0%
"%MYSQLDUMP%" -h 127.0.0.1 -u root asset_manager > "%BACKUP%"
forfiles /p "%PROJECT%\backups" /m *.sql /d -30 /c "cmd /c del @path"
