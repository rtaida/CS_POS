<?php if(isset($_GET['savedData'])): ?>
<script>
Swal.fire({ 
    icon: 'success', 
    title: 'Saved!', 
    text: 'Product has been saved successfully.' 
}).then(() => { 
    window.history.replaceState({}, document.title, window.location.pathname); 
});
</script>
<?php endif; ?>

<?php if(isset($_GET['updateData'])): ?>
<script>
Swal.fire({ 
    icon: 'success', 
    title: 'Updated!', 
    text: 'Product has been updated successfully.' 
}).then(() => { 
    window.history.replaceState({}, document.title, window.location.pathname); 
});
</script>
<?php endif; ?>

<?php if(isset($_GET['deleteData'])): ?>
<script>
Swal.fire({ 
    icon: 'success', 
    title: 'Deleted!', 
    text: 'Product has been deleted successfully.' 
}).then(() => { 
    window.history.replaceState({}, document.title, window.location.pathname); 
});
</script>
<?php endif; ?>

<?php if(isset($_GET['already'])): ?>
<script>
Swal.fire({ 
    icon: 'error', 
    title: 'Duplicate!', 
    text: 'Product code already exists.' 
}).then(() => { 
    window.history.replaceState({}, document.title, window.location.pathname); 
});
</script>
<?php endif; ?>

<?php if(isset($_GET['nothingChanged'])): ?>
<script>
Swal.fire({ 
    icon: 'info', 
    title: 'No Changes', 
    text: 'No changes were made to the product.' 
}).then(() => { 
    window.history.replaceState({}, document.title, window.location.pathname); 
});
</script>
<?php endif; ?>

<?php if(isset($_GET['error'])): ?>
<script>
Swal.fire({ 
    icon: 'error', 
    title: 'Error!', 
    text: 'Something went wrong. Please try again.' 
}).then(() => { 
    window.history.replaceState({}, document.title, window.location.pathname); 
});
</script>
<?php endif; ?>