'use strict';
const AnatomyViewer = {
    generation:0, root:null, selected:null, meshes:[],
    init(canvas) {
        this.canvas=canvas;
        this.renderer=new THREE.WebGLRenderer({canvas,antialias:true,preserveDrawingBuffer:true});
        this.renderer.setPixelRatio(Math.min(devicePixelRatio,2));
        this.scene=new THREE.Scene(); this.scene.background=new THREE.Color('#101c2e');
        this.camera=new THREE.PerspectiveCamera(40,1,.01,10000);
        this.scene.add(new THREE.HemisphereLight(0xffffff,0x536077,1.6));
        const light=new THREE.DirectionalLight(0xffffff,1.4);light.position.set(4,8,6);this.scene.add(light);
        this.controls=new THREE.OrbitControls(this.camera,canvas);this.controls.enableDamping=true;
        this.controls.enablePan=true;
        this.controls.screenSpacePanning=true;
        this.controls.touches.TWO=THREE.TOUCH.DOLLY_PAN;
        this.controls.listenToKeyEvents(canvas);
        this.raycaster=new THREE.Raycaster();
        // Intercept wheel zoom before OrbitControls' center-based wheel handler.
        // Moving camera and orbit target around the same anchor keeps it under the pointer.
        canvas.addEventListener('wheel',event=>{
            if(!this.root || !this.controls.enabled || !this.controls.enableZoom)return;
            event.preventDefault();event.stopImmediatePropagation();
            const units=event.deltaMode===1?16:event.deltaMode===2?canvas.clientHeight:1;
            const delta=Math.max(-200,Math.min(200,event.deltaY*units));
            this.zoom(Math.exp(delta*.002),this.pointerAnchor(event.clientX,event.clientY));
        },{capture:true,passive:false});
        this.observer=new ResizeObserver(()=>this.resize());this.observer.observe(canvas.parentElement);
        let pointerStart=null;
        canvas.addEventListener('pointerdown',e=>pointerStart={x:e.clientX,y:e.clientY});
        canvas.addEventListener('pointerup',e=>{
            if(e.button!==0 || !pointerStart || Math.hypot(e.clientX-pointerStart.x,e.clientY-pointerStart.y)>6)return;
            const rect=canvas.getBoundingClientRect();
            this.raycaster.setFromCamera(new THREE.Vector2((e.clientX-rect.left)/rect.width*2-1,-(e.clientY-rect.top)/rect.height*2+1),this.camera);
            const hit=this.raycaster.intersectObjects(this.meshes,false)[0];
            if(hit?.object.userData.structure) this.onSelect?.(hit.object.userData.structure);
        });
        this.animate=()=>{this.frame=requestAnimationFrame(this.animate);if(document.hidden)return;this.controls.update();this.renderer.render(this.scene,this.camera);};this.animate();
    },
    resize(){const rect=this.canvas.parentElement.getBoundingClientRect();this.renderer.setSize(rect.width,rect.height,false);this.camera.aspect=rect.width/Math.max(rect.height,1);this.camera.updateProjectionMatrix();},
    disposeRoot(root){root?.traverse(child=>{if(child.isMesh){child.geometry?.dispose();for(const material of (Array.isArray(child.material)?child.material:[child.material])){for(const value of Object.values(material)){if(value?.isTexture)value.dispose();}material.dispose();}}});},
    async load(system,onProgress){
        const generation=++this.generation;
        if(this.root){this.scene.remove(this.root);this.disposeRoot(this.root);this.root=null;}this.meshes=[];this.selected=null;
        if(!system.modelUrl)return false;
        const gltf=await new Promise((resolve,reject)=>new THREE.GLTFLoader().load(system.modelUrl,resolve,event=>onProgress?.(event.total?Math.round(event.loaded/event.total*100):null),reject));
        if(generation!==this.generation){this.disposeRoot(gltf.scene);return false;}
        this.root=gltf.scene;
        this.root.traverse(child=>{
            if(!child.isMesh)return;
            const part=(system.structures||[]).find(s=>s.mesh_name && (s.mesh_name===child.name || s.mesh_name===child.parent?.name));
            if(part){child.material=Array.isArray(child.material)?child.material.map(m=>m.clone()):child.material.clone();child.userData.structure=part;this.meshes.push(child);}
        });
        this.scene.add(this.root);this.resetView();return true;
    },
    resetView(){if(!this.root)return;const box=new THREE.Box3().setFromObject(this.root);const center=box.getCenter(new THREE.Vector3());const size=box.getSize(new THREE.Vector3()).length()||1;this.camera.position.copy(center).add(new THREE.Vector3(0,size*.1,size*1.4));this.camera.near=Math.max(size/1000,.001);this.camera.far=size*100;this.camera.updateProjectionMatrix();this.controls.target.copy(center);this.controls.update();},
    highlight(structure){
        for(const mesh of this.meshes){const materials=Array.isArray(mesh.material)?mesh.material:[mesh.material];for(const material of materials){if(!material.emissive)continue;if(material.userData.originalEmissive===undefined)material.userData.originalEmissive=material.emissive.getHex();material.emissive.setHex(mesh.userData.structure===structure?0x245f64:material.userData.originalEmissive);}}
    },
    pointerAnchor(x,y){
        const rect=this.canvas.getBoundingClientRect();
        this.camera.updateMatrixWorld();
        this.raycaster.setFromCamera(new THREE.Vector2((x-rect.left)/rect.width*2-1,-(y-rect.top)/rect.height*2+1),this.camera);
        // Raycast the whole model, including assets without individually labeled bones.
        const hit=this.root?this.raycaster.intersectObject(this.root,true)[0]:null;
        if(hit)return hit.point;
        const plane=new THREE.Plane().setFromNormalAndCoplanarPoint(this.camera.getWorldDirection(new THREE.Vector3()),this.controls.target);
        return this.raycaster.ray.intersectPlane(plane,new THREE.Vector3()) || this.controls.target.clone();
    },
    zoom(factor,anchor=this.controls.target.clone()){
        if(!this.root || !Number.isFinite(factor) || factor<=0)return;
        const distance=this.camera.position.distanceTo(this.controls.target);
        const next=THREE.MathUtils.clamp(distance*factor,Math.max(this.camera.near*10,this.controls.minDistance),Math.min(this.camera.far*.5,this.controls.maxDistance));
        const scale=next/Math.max(distance,Number.EPSILON);
        this.camera.position.sub(anchor).multiplyScalar(scale).add(anchor);
        this.controls.target.sub(anchor).multiplyScalar(scale).add(anchor);
        this.controls.update();
    },
    screenshot(){const link=document.createElement('a');link.download='anatomy-view.png';link.href=this.canvas.toDataURL('image/png');link.click();}
};
