雪里开PHP简易表单系统 - V1.0使用说明

一、系统简介
这是一个基于PHP和SQLite的表单管理系统，支持自定义表单字段、文件上传、数据管理等功能。
适合编程基础或熟练电脑操作者使用(编辑好json模板实现自定义万用表单，支持图片)
此为1+年前早期作品，实测可用(参考自带json模板)，今日翻出来分享大家使用。

二、环境要求
1. PHP版本：5.5-7.3
2. SQLite支持（PDO扩展）
3. PHP ZipArchive扩展（用于导出功能）

三、安装步骤
1. 将所有文件上传到网站目录
2. 确保以下目录可写：
   - 根目录（用于存储数据库文件）
   - uploads目录（用于存储上传文件）
   - exports目录（用于存储导出文件）
3. 首次访问系统会自动创建数据库和默认管理员账户

四、默认账户
用户名：admin
密码：admin123

五、文件结构
├── admin/          # 后台管理目录
│   ├── head.php    # 后台公共头部
│   ├── foot.php    # 后台公共底部
│   ├── isite.php   # 系统设置
│   ├── ilist.php   # 数据列表
│   ├── idown.php   # 统计导出
│   ├── ipass.php   # 修改密码
│   └── login.php   # 登录页面
├── inc/            # 公共文件目录
│   ├── conn.php    # 数据库连接
│   ├── pubs.php    # 公共函数
│   ├── sqls.php    # 数据库操作类
│   ├── js.js       # 公共JS
│   ├── admin.js    # 后台JS
│   └── css.css     # 样式文件
├── moban/          # 表单模板目录
├── uploads/        # 上传文件目录
└── exports/        # 导出文件目录

六、数据库结构
1. entries表（报名记录）
   - id: 主键
   - template_id: 模板ID
   - username: 用户名
   - password: 密码
   - email: 邮箱
   - idesc: 其他字段（JSON）
   - submit_date: 提交日期
   - submit_time: 提交时间
   - submit_ip: 提交IP
   - uniqid: 唯一标识
   - status: 状态（0未完成/1已完成）

2. attachments表（附件记录）
   - id: 主键
   - entry_id: 关联的报名记录ID
   - field_name: 字段名称
   - field_index: 字段序号
   - file_path: 文件路径
   - upload_time: 上传时间

七、使用注意事项
1. 文件上传
   - 文件存储路径规则：活动ID文件夹/信息ID文件夹/字段+序号.上传图片的后缀
   - 建议定期清理exports目录下的导出文件

2. 安全建议
   - 及时修改默认管理员密码
   - 确保admin目录不能被直接访问
   - 定期备份数据库文件

3. 性能优化
   - 系统使用json.php缓存配置信息
   - JS和CSS文件使用版本参数控制缓存

八、其他说明
1. 模板配置
   - 模板文件存放在moban目录下
   - 使用JSON格式配置表单字段
   - 可以通过后台系统设置选择不同模板

2. 数据导出
   - 支持导出CSV格式的报名数据
   - 支持打包下载所有附件文件
   - 导出文件会自动整理附件目录结构
