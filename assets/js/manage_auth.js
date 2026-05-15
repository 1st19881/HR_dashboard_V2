/**
 * manage_auth.js
 * จัดการหน้าจอการจัดสิทธิ์ผู้ใช้งาน
 */

$(document).ready(function() {
    loadAuthData();

    // ค้นหาชื่อพนักงานเมื่อเปลี่ยนรหัสพนักงาน
    $('#codempid').on('blur', function() {
        fetchEmployeeName($(this).val());
    });

    $('#btn-search-emp').on('click', function() {
        fetchEmployeeName($('#codempid').val());
    });

    // จัดการ Submit Form
    $('#authForm').on('submit', function(e) {
        e.preventDefault();
        saveAuth();
    });
});

let authDataTable;
let authRecords = [];

function loadAuthData() {
    fetch('api/manage_auth.php?action=list')
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text) });
            }
            return response.json();
        })
        .then(resp => {
            if (resp.status === 'success') {
                renderAuthTable(resp.data);
                updateStats(resp.data);
            } else {
                Swal.fire('Error', resp.message, 'error');
            }
        })
        .catch(err => {
            console.error('Error loading auth data:', err);
            $('#authTableBody').html(`<tr class="auth-empty-row"><td colspan="7" class="text-center text-danger py-5"><i class="fa-solid fa-triangle-exclamation me-2"></i> ไม่สามารถโหลดข้อมูลได้: ${escapeHtml(err.message)}</td></tr>`);
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + err.message, 'error');
        });
}

function renderAuthTable(data) {
    authRecords = Array.isArray(data) ? data : [];

    if ($.fn.DataTable.isDataTable('#authTable')) {
        $('#authTable').DataTable().destroy();
    }

    const tbody = $('#authTableBody');
    tbody.empty();

    authRecords.forEach(item => {
        const statusBadge = item.AUT_ACTIVE === 'Y' 
            ? '<span class="badge bg-success-subtle text-success px-3">Active</span>' 
            : '<span class="badge bg-danger-subtle text-danger px-3">Inactive</span>';
        
        const levelBadge = item.AUT_LEVEL == 99
            ? '<span class="badge bg-primary px-3">99 - Admin</span>'
            : '<span class="badge bg-secondary px-3">9 - User</span>';

        const autId = escapeJsString(item.AUT_ID);
        const name = escapeHtml(item.AUT_NAME);
        const code = escapeHtml(item.CODEMPID);
        const type = escapeHtml(item.AUT_TYPE || '-');
        const privilege = escapeHtml(item.AUT_PRIVILEGE || '-');
        const createDate = escapeHtml(item.CREATE_DATE_FMT || '-');

        const row = `
            <tr>
                <td class="ps-4 auth-person-cell" data-label="พนักงาน">
                    <div class="auth-user-cell">
                        <div class="auth-user-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <div class="auth-user-name">${name}</div>
                            <div class="auth-user-code">${code}</div>
                        </div>
                    </div>
                </td>
                <td data-label="ระดับการเข้าถึง">${levelBadge}</td>
                <td data-label="ประเภท"><span class="text-uppercase small fw-bold">${type}</span></td>
                <td data-label="กลุ่มโปรแกรม"><code class="small">${privilege}</code></td>
                <td data-label="สถานะ">${statusBadge}</td>
                <td class="small text-muted" data-label="วันที่สร้าง">${createDate}</td>
                <td class="text-end pe-4 auth-actions-cell" data-label="จัดการ">
                    <div class="auth-action-group">
                        <button class="btn btn-sm btn-outline-primary border-0 auth-icon-btn" onclick="editAuthById('${autId}')" title="แก้ไข" aria-label="แก้ไข">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger border-0 auth-icon-btn" onclick="deleteAuth('${autId}')" title="ลบ" aria-label="ลบ">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    authDataTable = $('#authTable').DataTable({
        pageLength: 10,
        autoWidth: false,
        columnDefs: [
            { targets: -1, orderable: false, searchable: false }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "ค้นหาผู้ใช้งาน...",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
            emptyTable: "ยังไม่มีข้อมูลสิทธิ์ผู้ใช้งาน",
            paginate: {
                previous: '<i class="fa-solid fa-chevron-left"></i>',
                next: '<i class="fa-solid fa-chevron-right"></i>'
            }
        },
        dom: '<"auth-table-toolbar"f>t<"auth-table-footer"ip>',
    });
}

function updateStats(data) {
    const rows = Array.isArray(data) ? data : [];
    $('#stat-total-users').text(rows.length);
    $('#stat-active-users').text(rows.filter(i => i.AUT_ACTIVE === 'Y').length);
    $('#stat-inactive-users').text(rows.filter(i => i.AUT_ACTIVE !== 'Y').length);
    $('#stat-admin-users').text(rows.filter(i => Number(i.AUT_LEVEL) === 99 || String(i.AUT_TYPE).toLowerCase() === 'admin').length);
}

function fetchEmployeeName(codempid) {
    if (!codempid) return;
    
    fetch(`api/manage_auth.php?action=get_emp&codempid=${codempid}`)
        .then(res => res.json())
        .then(resp => {
            if (resp.status === 'success') {
                $('#aut_name').val(resp.name);
            } else {
                $('#aut_name').val('');
                Swal.fire({
                    icon: 'warning',
                    title: 'คำแจ้งเตือน',
                    text: resp.message,
                    timer: 2000
                });
            }
        })
        .catch(err => {
            console.error('Error fetching employee:', err);
            $('#aut_name').val('');
        });
}

function openAddModal() {
    $('#authModalTitle').text('เพิ่มผู้ใช้งานระบบ');
    $('#authForm')[0].reset();
    $('#aut_id').val('');
    $('#aut_active').prop('checked', true);
    $('#codempid').prop('readOnly', false);
    
    const modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
}

function editAuth(item) {
    $('#authModalTitle').text('แก้ไขข้อมูลผู้ใช้งาน');
    $('#aut_id').val(item.AUT_ID);
    $('#codempid').val(item.CODEMPID).prop('readOnly', true);
    $('#aut_name').val(item.AUT_NAME);
    $('#aut_level').val(item.AUT_LEVEL);
    $('#aut_type').val(item.AUT_TYPE || 'User');
    $('#aut_privilege').val(item.AUT_PRIVILEGE);
    $('#aut_active').prop('checked', item.AUT_ACTIVE === 'Y');

    const modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
}

function editAuthById(id) {
    const item = authRecords.find(row => String(row.AUT_ID) === String(id));
    if (!item) {
        Swal.fire('Error', 'ไม่พบข้อมูลผู้ใช้งานที่ต้องการแก้ไข', 'error');
        return;
    }
    editAuth(item);
}

function saveAuth() {
    const formData = new FormData(document.getElementById('authForm'));
    // จัดการ Switch value
    if (!formData.has('aut_active')) {
        formData.append('aut_active', 'N');
    }

    fetch('api/manage_auth.php?action=save', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: resp.message,
                timer: 1500,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
            loadAuthData();
        } else {
            Swal.fire('Error', resp.message, 'error');
        }
    })
    .catch(err => console.error('Error saving auth:', err));
}

function deleteAuth(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบสิทธิ์การเข้าใช้งานนี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('aut_id', id);

            fetch('api/manage_auth.php?action=delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.status === 'success') {
                    Swal.fire('ลบแล้ว!', resp.message, 'success');
                    loadAuthData();
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
            });
        }
    });
}

function escapeHtml(value) {
    return $('<div>').text(value ?? '').html();
}

function escapeJsString(value) {
    return String(value ?? '')
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '&quot;')
        .replace(/\r?\n/g, ' ');
}
