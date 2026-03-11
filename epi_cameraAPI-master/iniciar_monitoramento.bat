@echo off
setlocal

:: Navega até o diretório do projeto e inicia os servidores em segundo plano usando pythonw

echo Iniciando Servidor EPI (Porta 5000)...
start /b "" pythonw "C:\xampp\htdocs\EPI-original-trabalho-master_camerasAtuais\EPI-original-trabalho-bia\senaiEpi_Ia\servidor_camera.py"

echo Iniciando Servidor Facial (Porta 5001)...
start /b "" pythonw "C:\xampp\htdocs\EPI-original-trabalho-master_camerasAtuais\EPI-original-trabalho-bia\senaiEpi_Ia\Trabalho-E.P.I\reconhecimento_facial\sistema_final.py"

echo.
echo [SUCESSO] Os servidores estao rodando em segundo plano.
echo Voce ja pode testar o monitoramento no site.
echo Use o arquivo 'parar_monitoramento.bat' para encerrar.
echo.
pause
