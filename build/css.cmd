@echo off
call lessc "%~dp0css\translator.less" "%~dp0..\integrated_localization\css\translator.css"
if errorlevel 1 pause
