import { useState } from 'react';
import LookupSelect from '../components/LookupSelect';
import { Input, ValidatedControl } from '../components/WorkspaceForm';
function categorySlug(value) {
  return value.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 120).replace(/-+$/g, '');
}
export default function CategoryFields({
  category,
  categories
}) {
  const [slug, setSlug] = useState(category.slug || '');
  const [customSlug, setCustomSlug] = useState(Boolean(category.id));
  return <>
    <Input name="name" label="Name" value={category.name} onChange={event => {
      if (!customSlug) setSlug(categorySlug(event.target.value));
    }} />
    <label>Category slug
      <ValidatedControl><input name="slug" value={slug} required maxLength={120} pattern="[a-z0-9-]+" placeholder="school-uniforms" aria-describedby="category-slug-help" title="Use lowercase English letters, numbers and hyphens, for example school-uniforms." onChange={event => {
          setCustomSlug(true);
          setSlug(event.target.value);
        }} onBlur={event => { const normalized = categorySlug(event.target.value); event.target.value = normalized; setSlug(normalized); }} aria-label={"Category slug"} /></ValidatedControl>
    </label>
    <p className="footnote" id="category-slug-help">Generated from the name for new categories. You can edit it; use lowercase English letters, numbers and hyphens, for example school-uniforms.</p>
    <LookupSelect name="parent_id" label="Parent category" endpoint="catalog/categories" value={category.parent_id} options={categories} required={false} />
    <p className="footnote">For a top-level category, leave Parent category blank. To create a subcategory, first save its parent on this page, then select that parent here.</p>
    <label className="checkbox-label"><ValidatedControl><input name="active" type="checkbox" defaultChecked={category.active ?? true} aria-label={"Active"} /></ValidatedControl>Active</label>
  </>;
}
