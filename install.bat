@echo off
chcp 65001 >nul
echo ========================================
echo    AI助手增强博客系统 - 快速安装脚本
echo ========================================
echo.

echo [1/4] 检查环境...
where mysql >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ 未找到MySQL，请先安装MySQL
    pause
    exit /b 1
)

where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ 未找到PHP，请先安装PHP
    pause
    exit /b 1
)

echo ✅ 环境检查通过

echo.
echo [2/4] 创建数据库...
set /p db_user="请输入MySQL用户名 (默认: root): "
if "%db_user%"=="" set db_user=root

set /p db_pass="请输入MySQL密码: "

echo 正在创建数据库...
mysql -u %db_user% -p%db_pass% < database/create_database.sql
if %errorlevel% neq 0 (
    echo ❌ 数据库创建失败
    pause
    exit /b 1
)

echo 正在初始化数据...
mysql -u %db_user% -p%db_pass% < database/init_data.sql
if %errorlevel% neq 0 (
    echo ❌ 数据初始化失败
    pause
    exit /b 1
)

echo ✅ 数据库创建完成

echo.
echo [3/4] 配置后端...
echo 正在更新数据库配置...

powershell -Command "(Get-Content backend/config/database.php) -replace 'root', '%db_user%' | Set-Content backend/config/database.php"
powershell -Command "(Get-Content backend/config/database.php) -replace \"'password' => 'root'\", \"'password' => '%db_pass%'\" | Set-Content backend/config/database.php"

echo ✅ 后端配置完成

echo.
echo [4/4] 安装前端依赖...
cd ai-blog-frontend
where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️  未找到npm，跳过前端依赖安装
    echo    请手动运行: cd ai-blog-frontend && npm install
) else (
    echo 正在安装前端依赖...
    npm install
    if %errorlevel% neq 0 (
        echo ❌ 前端依赖安装失败
    ) else (
        echo ✅ 前端依赖安装完成
    )
)

cd ..

echo.
echo ========================================
echo           🎉 安装完成！
echo ========================================
echo.
echo 默认管理员账户:
echo   用户名: admin
echo   密码: 123456
echo.
echo 启动方式:
echo   1. 后端: 将 backend 目录配置为Web服务器根目录
echo   2. 前端: cd ai-blog-frontend && npm run dev
echo.
echo 访问地址:
echo   前端: http://localhost:5173
echo   后端API: http://localhost/backend
echo.
echo 详细说明请查看 README.md 文件
echo ========================================
pause