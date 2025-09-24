@echo off
chcp 65001 >nul
echo ========================================
echo    AI助手增强博客系统 - 一键部署脚本
echo ========================================
echo.

echo [步骤 1/6] 环境检查...
echo 检查MySQL...
where mysql >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ 未找到MySQL，请先安装MySQL
    echo    下载地址: https://dev.mysql.com/downloads/mysql/
    pause
    exit /b 1
)

echo 检查PHP...
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ 未找到PHP，请先安装PHP
    echo    推荐使用PHPStudy: https://www.xp.cn/
    pause
    exit /b 1
)

echo 检查Node.js...
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️  未找到Node.js，将跳过前端部署
    echo    下载地址: https://nodejs.org/
    set SKIP_FRONTEND=1
) else (
    set SKIP_FRONTEND=0
)

echo ✅ 环境检查完成
echo.

echo [步骤 2/6] 配置数据库...
set /p db_host="请输入MySQL主机地址 (默认: localhost): "
if "%db_host%"=="" set db_host=localhost

set /p db_port="请输入MySQL端口 (默认: 3306): "
if "%db_port%"=="" set db_port=3306

set /p db_user="请输入MySQL用户名 (默认: root): "
if "%db_user%"=="" set db_user=root

set /p db_pass="请输入MySQL密码: "

set /p db_name="请输入数据库名称 (默认: ai_blog_system): "
if "%db_name%"=="" set db_name=ai_blog_system

echo.
echo [步骤 3/6] 创建环境配置文件...
(
echo # 数据库配置
echo DB_HOST=%db_host%
echo DB_PORT=%db_port%
echo DB_NAME=%db_name%
echo DB_USER=%db_user%
echo DB_PASS=%db_pass%
echo.
echo # JWT配置
echo JWT_SECRET=%RANDOM%%RANDOM%%RANDOM%
echo JWT_EXPIRE=7200
echo.
echo # 系统配置
echo SITE_NAME=AI助手增强博客系统
echo SITE_URL=http://localhost
echo DEBUG=true
) > backend\config\.env

echo ✅ 环境配置文件创建完成
echo.

echo [步骤 4/6] 初始化数据库...
echo 正在创建数据库...
mysql -h %db_host% -P %db_port% -u %db_user% -p%db_pass% < database\create_database.sql
if %errorlevel% neq 0 (
    echo ❌ 数据库创建失败，请检查MySQL连接信息
    pause
    exit /b 1
)

echo 正在初始化数据...
mysql -h %db_host% -P %db_port% -u %db_user% -p%db_pass% < database\init_data.sql
if %errorlevel% neq 0 (
    echo ❌ 数据初始化失败
    pause
    exit /b 1
)

echo ✅ 数据库初始化完成
echo.

echo [步骤 5/6] 测试后端连接...
echo 正在测试系统状态...
php -f backend\api\system\status.php > nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️  后端测试失败，请检查PHP配置
) else (
    echo ✅ 后端连接正常
)
echo.

if %SKIP_FRONTEND%==0 (
    echo [步骤 6/6] 安装前端依赖...
    cd ai-blog-frontend
    echo 正在安装前端依赖...
    npm install
    if %errorlevel% neq 0 (
        echo ❌ 前端依赖安装失败
        cd ..
    ) else (
        echo ✅ 前端依赖安装完成
        cd ..
    )
) else (
    echo [步骤 6/6] 跳过前端安装
)

echo.
echo ========================================
echo           🎉 部署完成！
echo ========================================
echo.
echo 📋 系统信息:
echo   数据库: %db_name%@%db_host%:%db_port%
echo   后端目录: backend/
echo   前端目录: ai-blog-frontend/
echo.
echo 👤 默认管理员账户:
echo   用户名: admin
echo   密码: 123456
echo.
echo 🚀 启动方式:
if %SKIP_FRONTEND%==0 (
    echo   前端: cd ai-blog-frontend ^&^& npm run dev
)
echo   后端: 配置Web服务器指向backend目录
echo.
echo 🌐 访问地址:
if %SKIP_FRONTEND%==0 (
    echo   前端: http://localhost:5173
)
echo   后端API: http://localhost/backend
echo   系统状态: http://localhost/backend/api/system/status
echo.
echo 📖 详细文档: README.md
echo ========================================

if %SKIP_FRONTEND%==0 (
    echo.
    set /p start_frontend="是否立即启动前端开发服务器? (y/n): "
    if /i "%start_frontend%"=="y" (
        echo 正在启动前端服务器...
        cd ai-blog-frontend
        start cmd /k "npm run dev"
        cd ..
    )
)

pause