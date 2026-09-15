'use strict';
const AnatomyData = {
    systems: [],
    async load() {
        const response = await fetch(API_BASE + '/anatomy-content.php', {credentials:'same-origin'});
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Unable to load anatomy content.');
        this.systems = result.data.map(row => ({
            id:row.system_code, system_id:row.system_id, name:row.system_name, isActive:row.is_active,
            icon:row.icon_emoji || '', color:row.color_hex, description:row.description || '',
            keyFacts:row.key_facts || {}, structures:row.structures || [],
            modelUrl:row.model_url, source:row.source_text || ''
        }));
        return this.systems;
    },
    getSystem(id) { return this.systems.find(system => system.id === id); },
    getAllSystems() { return this.systems; }
};
