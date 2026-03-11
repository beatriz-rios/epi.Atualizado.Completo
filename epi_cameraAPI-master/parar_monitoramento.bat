@echo off
echo Encerrando servidores Python em segundo plano...

:: Finaliza todos os processos pythonw.exe
taskkill /f /im pythonw.exe

echo.
echo [SUCESSO] Os servidores foram encerrados.
echo.
pause
