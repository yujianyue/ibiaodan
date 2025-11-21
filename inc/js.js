// Ajax communication function
function ajax(options) {
    var xhr = new XMLHttpRequest();
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (options.success) {
                        options.success(response);
                    }
                } catch (e) {
                    if (options.error) {
                        options.error(e);
                    }
                }
            } else {
                if (options.error) {
                    options.error(xhr.statusText);
                }
            }
        }
    };
    
    xhr.open(options.method || 'GET', options.url, true);
    
    if (options.data instanceof FormData) {
        xhr.send(options.data);
    } else if (typeof options.data === 'object') {
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify(options.data));
    } else {
        xhr.send();
    }
}

// Show loading mask
function showLoading() {
    var mask = document.createElement('div');
    mask.className = 'loading-mask';
    mask.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(mask);
}

// Hide loading mask
function hideLoading() {
    var mask = document.querySelector('.loading-mask');
    if (mask) {
        mask.parentNode.removeChild(mask);
    }
}

// Format date time
function formatDateTime(datetime) {
    if (!datetime) return '';
    var d = new Date(datetime);
    return d.getFullYear() + '-' + 
           padZero(d.getMonth() + 1) + '-' + 
           padZero(d.getDate()) + ' ' + 
           padZero(d.getHours()) + ':' + 
           padZero(d.getMinutes()) + ':' + 
           padZero(d.getSeconds());
}

// Pad zero for numbers less than 10
function padZero(num) {
    return num < 10 ? '0' + num : num;
}

// Get query string parameter
function getQueryString(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return decodeURIComponent(r[2]); return null;
}
