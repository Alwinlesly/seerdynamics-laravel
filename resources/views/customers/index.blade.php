@extends('layouts.app')

@section('content')
<div class="main">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex align-items-center gap-3">
            <div class="hamburger is-lg">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </div>
            <div class="logo">
                <img src="{{ asset('assets/img/logo-360x103.png') }}" alt="">
            </div>
        </div>

        <div class="top-right">
            <div class="profile-container" id="profilePopover" data-bs-toggle="popover" data-bs-html="true"
                data-bs-placement="bottom">
                <img src="{{ asset('assets/img/mrs1.webp') }}" alt="User Profile">
                <div class="profile-info">
                    <div class="name">{{ $current_user->first_name }} {{ $current_user->last_name }}</div>
                    <div class="email">{{ $current_user->email }}</div>
                </div>
                <div class="dropdown-icon">▼</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4">
                <div class="header">
                    <h1 class="pg-hd"><b>Customers</b></h1>
                    <div class="search-create">
                        <div class="search-wrap">
                            <input type="search" class="form-control form-control-sm py-2" id="searchInput" placeholder="Search" />
                            <span class="search-icon d-flex">
                                <svg fill="#9A9A9A" width="15px" height="15px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21.71,20.29,18,16.61A9,9,0,1,0,16.61,18l3.68,3.68a1,1,0,0,0,1.42,0A1,1,0,0,0,21.71,20.29ZM11,18a7,7,0,1,1,7-7A7,7,0,0,1,11,18Z" />
                                </svg>
                            </span>
                        </div>
                        <button class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
                            <span>
                                <svg fill="#fff" width="20px" height="20px" viewBox="0 0 24 24" id="plus" data-name="Flat Color" xmlns="http://www.w3.org/2000/svg" class="icon flat-color">
                                    <path id="primary" d="M12,20a1,1,0,0,1-1-1V13H5a1,1,0,0,1,0-2h6V5a1,1,0,0,1,2,0v6h6a1,1,0,0,1,0,2H13v6A1,1,0,0,1,12,20Z" style="fill: #fff"></path>
                                </svg>
                            </span> Create
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div>
                <div class="p-4 pt-0">
                    <div class="table-responsive table-x table-consultant">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="text-muted fw-medium">Customer</th>
                                        <th scope="col" class="text-muted fw-medium">Email</th>
                                        <th scope="col" class="text-muted fw-medium">Contact person</th>
                                        <th scope="col" class="text-muted fw-medium">Address</th>
                                        <th scope="col" class="text-muted fw-medium">Project</th>
                                        <th scope="col" class="text-muted fw-medium">Status</th>
                                        <th scope="col" class="text-muted fw-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="customersTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div>
                            <div class="d-flex align-items-center gap-2 pagination">
                                <span class="current-total">1</span>
                                <span class="text-muted">of <span id="totalCustomers">0</span></span>
                                <div class="d-flex">
                                    <button class="btn btn-light d-flex align-items-center" id="prevBtn" disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                                        </svg>
                                    </button>
                                    <button class="btn btn-light d-flex align-items-center next-btn" id="nextBtn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('partials.footer')
            </div>
        </div>
    </div>
</div>

<!-- Include Modals -->
@include('customers.modals.create')
@include('customers.modals.edit')
@include('customers.modals.delete')

@push('scripts')
<script>
$(document).ready(function() {
    let searchTimeout;
    
    // Load customers on page load
    loadCustomers();
    
    // Search with debounce
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadCustomers();
        }, 500);
    });
    
    // Load customers function
    function loadCustomers() {
        const searchValue = $('#searchInput').val();
        
        $.ajax({
            url: '{{ route("customers.list") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                search: searchValue
            },
            success: function(response) {
                if (!response.error) {
                    renderCustomers(response.customers);
                } else {
                    showError('Failed to load customers');
                }
            },
            error: function() {
                showError('Failed to load customers');
            }
        });
    }
    
    // Render customers table
    function renderCustomers(customers) {
        const tbody = $('#customersTableBody');
        tbody.empty();
        
        if (customers.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="7" class="text-center py-4">No customers found</td>
                </tr>
            `);
            $('#totalCustomers').text('0');
            return;
        }
        
        $('#totalCustomers').text(customers.length);
        
        customers.forEach(function(customer) {
            const customerCode = customer.customer_code || 'N/A';
            const avatarHtml = customer.profile_picture 
                ? `<img src="${customer.profile_picture}" alt="${customer.name}" class="rounded-circle profile-img">`
                : `<div class="avatar-circle avatar-gradient">${customerCode}</div>`;
            
            const statusClass = customer.status === 'Active' ? 'status-active' : 'status-incative';
            
            const row = `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2 con_name">
                            ${avatarHtml}
                            <span class="fw-medium">${customer.name}</span>
                        </div>
                    </td>
                    <td>${customer.email}</td>
                    <td>${customer.contact_person}</td>
                    <td>${customer.address}</td>
                    <td>${customer.project_count}</td>
                    <td><span class="${statusClass}">${customer.status}</span></td>
                    <td>
                        <div class="d-flex gap-2 align-items-center justify-content-center">
                            <span class="edit-customer" data-id="${customer.id}" style="cursor: pointer;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </span>
                            <span class="delete-customer" data-id="${customer.id}" style="cursor: pointer;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                </svg>
                            </span>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    // Helper function to get initials
    function getInitials(firstName, lastName) {
        const first = firstName ? firstName.charAt(0).toUpperCase() : '';
        const last = lastName ? lastName.charAt(0).toUpperCase() : '';
        return first + last;
    }
    
    // Show error message
    function showError(message) {
        $('#customersTableBody').html(`
            <tr>
                <td colspan="7" class="text-center text-danger py-4">${message}</td>
            </tr>
        `);
    }
    
    // Edit customer
    $(document).on('click', '.edit-customer', function() {
        const customerId = $(this).data('id');
        
        $.ajax({
            url: `/customers/${customerId}/edit`,
            method: 'GET',
            success: function(response) {
                if (!response.error) {
                    const customer = response.customer;
                    $('#editCustomerId').val(customer.id);
                    $('#editFirstName').val(customer.first_name);
                    $('#editLastName').val(customer.last_name);
                    $('#editEmail').val(customer.email);
                    $('#editCompany').val(customer.company);
                    $('#editPhone').val(customer.phone);
                    $('#editContactPerson').val(customer.contact_person_desg);
                    $('#editAddress').val(customer.address);
                    $('#editCountry').val(customer.country);
                    $('#editActive').prop('checked', customer.active == 1);
                    
                    $('#editCustomerModal').modal('show');
                } else {
                    alert('Failed to load customer data');
                }
            },
            error: function() {
                alert('Failed to load customer data');
            }
        });
    });
    
    // Delete customer
    $(document).on('click', '.delete-customer', function() {
        const customerId = $(this).data('id');
        $('#deleteCustomerId').val(customerId);
        $('#deleteCustomerModal').modal('show');
    });
});
</script>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    font-size: 14px;
}

.avatar-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.profile-img {
    width: 40px;
    height: 40px;
}
</style>
@endpush
@endsection
