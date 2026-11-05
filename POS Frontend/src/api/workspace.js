import { client } from './client';
import { notificationScope, notify } from '../notifications';

export const workspaceApi = {
  get: async (path, config) => (await client.get(`/api/v1/${path}`, config)).data,
  save: async (path, data, update = false) => (await client[update ? 'patch' : 'post'](`/api/v1/${path}`, data)).data,
  remove: async path => client.delete(`/api/v1/${path}`),
  exportCatalog: async () => {
    const scope = notificationScope();
    const { data } = await client.get('/api/v1/products/export', { responseType: 'blob' });
    if (scope !== notificationScope()) return;
    const url = URL.createObjectURL(data);
    const link = document.createElement('a'); link.href = url; link.download = 'catalog.csv'; link.click(); URL.revokeObjectURL(url);
    notify.success('Catalog download prepared.');
  },
  image: id => `${client.defaults.baseURL || ''}/api/v1/catalog-images/${id}`,
};
