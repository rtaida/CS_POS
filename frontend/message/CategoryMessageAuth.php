<?php if(isset($_GET['savedData'])): ?>
<script>
Swal.fire({ icon: 'success', title: 'Saved!', text: 'Category has been saved successfully.' })
.then(() => { window.history.replaceState({}, document.title, window.location.pathname); });
</script>
<?php endif; ?>

<?php if(isset($_GET['updateData'])): ?>
<script>
Swal.fire({ icon: 'success', title: 'Updated!', text: 'Category has been updated successfully.' })
.then(() => { window.history.replaceState({}, document.title, window.location.pathname); });
</script>
<?php endif; ?>

<?php if(isset($_GET['deleteData'])): ?>
<script>
Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Category has been deleted successfully.' })
.then(() => { window.history.replaceState({}, document.title, window.location.pathname); });
</script>
<?php endif; ?>

<?php if(isset($_GET['already'])): ?>
<script>
Swal.fire({ icon: 'error', title: 'Duplicate!', text: 'Category name already exists.' })
.then(() => { window.history.replaceState({}, document.title, window.location.pathname); });
</script>
<?php endif; ?>