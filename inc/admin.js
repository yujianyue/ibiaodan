// Sort function
function sortBy(th) {
    var field = th.getAttribute('data-field');
    var headers = document.querySelectorAll('th[data-field]');
    
    headers.forEach(function(header) {
        if (header !== th) {
            header.classList.remove('asc', 'desc');
        }
    });
    
    if (!th.classList.contains('asc') && !th.classList.contains('desc')) {
        th.classList.add('asc');
        currentOrder = {field: field, direction: 'ASC'};
    } else if (th.classList.contains('asc')) {
        th.classList.remove('asc');
        th.classList.add('desc');
        currentOrder = {field: field, direction: 'DESC'};
    } else {
        th.classList.remove('desc');
        currentOrder = {field: 'id', direction: 'DESC'};
    }
    
    loadData();
}

// Pagination function
function renderPagination(totalPages) {
    var html = '';
    if (totalPages > 1) {
        html += `
            <a href="javascript:;" onclick="gotoPage(1)" class="${currentPage == 1 ? 'disabled' : ''}">首页</a>
            <a href="javascript:;" onclick="gotoPage(${currentPage - 1})" class="${currentPage == 1 ? 'disabled' : ''}">上一页</a>
            <select onchange="gotoPage(this.value)">
        `;
        
        for (var i = 1; i <= totalPages; i++) {
            html += `<option value="${i}" ${currentPage == i ? 'selected' : ''}>第${i}页</option>`;
        }
        
        html += `
            </select>
            <a href="javascript:;" onclick="gotoPage(${currentPage + 1})" class="${currentPage == totalPages ? 'disabled' : ''}">下一页</a>
            <a href="javascript:;" onclick="gotoPage(${totalPages})" class="${currentPage == totalPages ? 'disabled' : ''}">末页</a>
        `;
    }
    document.getElementById('pagination').innerHTML = html;
}

function gotoPage(page) {
    page = parseInt(page);
    if (page < 1) return;
    currentPage = page;
    loadData();
}

// Search function
function searchData() {
    currentPage = 1;
    loadData();
}

// Toggle all checkboxes
function toggleAll(checkbox) {
    var checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(function(item) {
        item.checked = checkbox.checked;
    });
}

// Batch delete
function batchDelete() {
    var checkboxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    if (checkboxes.length === 0) {
        alert('请选择要删除的记录');
        return;
    }
    
    if (!confirm('确定要删除选中的记录吗？')) {
        return;
    }
    
    var ids = [];
    checkboxes.forEach(function(item) {
        ids.push(item.value);
    });
    
    ajax({
        url: '?act=dels',
        method: 'POST',
        data: {
            action: 'delete',
            ids: ids
        },
        success: function(res) {
            alert(res.msg);
            if (res.code == 1) {
                //loadData();
            }
        }
    });
}

// Delete single entry
function deleteEntry(id) {
    if (!confirm('确定要删除这条记录吗？')) {
        return;
    }
    
    ajax({
        url: '?act=del',
        method: 'POST',
        data: {
            action: 'delete',
            ids: [id]
        },
        success: function(res) {
            alert(res.msg);
            if (res.code == 1) {
                loadData();
            }
        }
    });
}

// Update status
function updateStatus(id, status) {
    ajax({
        url: '?act=status',
        method: 'POST',
        data: {
            action: 'status',
            id: id,
            status: status
        },
        success: function(res) {
            if (res.code == 0) {
                alert(res.msg);
                loadData();
            }
        }
    });
}

// Show detail modal
function showDetail(id) {
    ajax({
        url: '?act=detail',
        method: 'POST',
        data: {
            action: 'detail',
            id: id
        },
        success: function(res) {
            if (res.code == 1) {
                var modal = document.getElementById('detailModal');
                var info = document.getElementById('info');
                var files = document.getElementById('files');
                
                // Render basic info
                var data = res.data;
                var idesc = JSON.parse(data.idesc);
                var infoHtml = `
                    <table class="detail-table">
                        <tr><td>ID：</td><td>${data.id}</td></tr>
                        <tr><td>模板：</td><td>${data.template_id}</td></tr>
                        <tr><td>用户名：</td><td>${data.username}</td></tr>
                        <tr><td>邮箱：</td><td>${data.email}</td></tr>
                        <tr><td>提交日期：</td><td>${data.submit_date}</td></tr>
                        <tr><td>提交时间：</td><td>${data.submit_time}</td></tr>
                        <tr><td>IP地址：</td><td>${data.submit_ip}</td></tr>
                        <tr><td>状态：</td><td>${data.status == 1 ? '已完成' : '未完成'}</td></tr>
                `;
                
                // Add custom fields
                for (var key in idesc) {
                    infoHtml += `<tr><td>${key}：</td><td>${idesc[key]}</td></tr>`;
                }
                infoHtml += '</table>';
                info.innerHTML = infoHtml;
                
                // Render attachments
                var filesHtml = '<table class="detail-table">';
                if (data.attachments && data.attachments.length > 0) {
                    data.attachments.forEach(function(file) {
                        filesHtml += `
                            <tr>
                                <td>${file.field_name}${file.field_index}</td>
                                <td><a href="${file.file_path}" target="_blank">查看文件</a></td>
                            </tr>
                        `;
                    });
                } else {
                    filesHtml += '<tr><td colspan="2">无附件</td></tr>';
                }
                filesHtml += '</table>';
                files.innerHTML = filesHtml;
                
                // Show modal
                modal.style.display = 'block';
            } else {
                alert(res.msg);
            }
        }
    });
}

// Close modal
function closeModal() {
    var modal = document.getElementById('detailModal');
    modal.style.display = 'none';
}

// Tab switching
document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('.tab-header span');
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var tabId = this.getAttribute('data-tab');
            
            // Update tab headers
            tabs.forEach(function(t) {
                t.classList.remove('active');
            });
            this.classList.add('active');
            
            // Update tab content
            var panes = document.querySelectorAll('.tab-pane');
            panes.forEach(function(pane) {
                pane.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
        });
    });
});
