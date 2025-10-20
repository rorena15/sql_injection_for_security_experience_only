const id = document.querySelector('#username');
const pw = document.querySelector('#password');
const btnContainer = document.querySelector('.btn_container');
const btn = document.querySelector('#submit');

btn.disabled = true;

function shiftButton(){
    const position = ['shift-left', 'shift-right',
                    'shift-top' , 'shift-bottom'];
    const currentPosition = position.find(dir => btn.classList.contains(dir));                
                
    const nextPosition = position [(position
                                        .indixOf(currentPosition) + 1) % position.length];
    
    btn.classList.remove(currentPosition);
    btn.classList.add(nextPosition);
}