@echo off
echo ============================================
echo Tao SSH Key cho GitHub Actions
echo ============================================
echo.

set KEY_PATH=%USERPROFILE%\.ssh\github_actions_visionboard

echo Tao SSH key tai: %KEY_PATH%
echo.

ssh-keygen -t ed25519 -C "github-actions-visionboard" -f "%KEY_PATH%" -N ""

if %errorlevel% equ 0 (
    echo.
    echo ============================================
    echo THANH CONG! SSH key da duoc tao
    echo ============================================
    echo.
    echo Private key: %KEY_PATH%
    echo Public key:  %KEY_PATH%.pub
    echo.
    echo.
    echo ============================================
    echo BUOC TIEP THEO:
    echo ============================================
    echo.
    echo 1. Copy PUBLIC KEY nay len server:
    echo.
    type "%KEY_PATH%.pub"
    echo.
    echo.
    echo 2. SSH vao server va chay:
    echo    mkdir -p ~/.ssh
    echo    nano ~/.ssh/authorized_keys
    echo    ^(Paste public key vao, save va exit^)
    echo    chmod 600 ~/.ssh/authorized_keys
    echo.
    echo 3. Copy PRIVATE KEY nay vao GitHub Secret SSH_PRIVATE_KEY:
    echo    File: %KEY_PATH%
    echo    ^(Mo file bang Notepad va copy toan bo noi dung^)
    echo.
    pause
) else (
    echo.
    echo LOI: Khong the tao SSH key
    echo Vui long thu lai hoac tao thu cong
    pause
)
