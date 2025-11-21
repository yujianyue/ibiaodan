启用过的模板一般不修改，新版本用新文件名然后后台选择。

前三个字段name固定不变，一般用于唯一对应用户值/区分值的填写(比如姓名、身份证号、手机号、邮件地址等)
name不重复，
其他的参考规律(json)：
[
[
    {"name": "username", "label": "用户名", "type": "text", "required": true, "pattern": "user", "index": "yujianyue"},
    {"name": "phone", "label": "手机号", "type": "text", "required": true, "pattern": "mobi", "index": "15555555555"},
    {"name": "email", "label": "邮箱", "type": "text", "required": true, "pattern": "mail", "index": "admin@126.com"},
    {"name": "password", "label": "密码", "type": "password", "required": true, "index": "253252545"},
    {"name": "yuedate", "label": "预约日期", "type": "date", "required": true, "index": "2024-12-28"},
    {"name": "yuetime", "label": "预约时间", "type": "time", "required": true, "index": "20:20"},	
    {"name": "irens", "label": "参与人数", "type": "number", "required": true, "index": "5"},
    {"name": "feedback", "label": "反馈内容", "type": "textarea", "required": true, "index": "欢迎光临!"},
    {"name": "gender", "label": "性别", "type": "select", "options": ["男", "女"], "required": true, "index": "女"}
],
[
    {"name": "files", "label": "上传文档a", "type": "idocx", "exts": ".doc,.docx,.xls,.xlsx,.pdf", "required": true, "maxFiles": 2},
    {"name": "imges", "label": "上传图片a", "type": "imges", "exts": ".jpg,.jpeg,.png,.gif", "required": true, "minFiles": 1, "maxFiles": 3, "maxSizes": 3},
	{"name": "zipes", "label": "传压缩包a", "type": "zfile", "exts": ".zip,.rar", "required": true, "maxFiles": 2}
]
]
