function deleteNews(id) {
    if (!confirm('News wirklich löschen?')) return;
    submitDeleteForm('/admin/news/' + id + '/delete');
}

function deleteBlog(id) {
    if (!confirm('Artikel wirklich löschen?')) return;
    submitDeleteForm('/admin/blog/' + id + '/delete');
}

function deleteProduct(id) {
    if (!confirm('Produkt wirklich löschen?')) return;
    submitDeleteForm('/admin/products/' + id + '/delete');
}

function submitDeleteForm(action) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    document.body.appendChild(form);
    form.submit();
}
