const backdropID = 'bringBooking_create_backdrop';
let order_id = null;

let input_shippingDate = {
  year: '', month: '', day: ''
}


const isInteger = val => {
  if (typeof val !== "string" && typeof val !== 'number' ) return false;
  return !isNaN(val) && !isNaN(parseFloat(val)) && Number.isInteger(Number(val));
}


// =============== modal close handler ====================
const modal_close_handler = () => {
  const backdropDiv = document.getElementById(backdropID);
  backdropDiv.style.display = 'none';
  backdropDiv.innerHTML = "";
}


// ================= order backdrop ui generator =================
const inputBoxGenerator = ({ label, clsName, placeholder, width, value }) => {

  let inputHtml =  `
   <div class="bringBooking_inputDiv"
  `;

  if(width) {
    inputHtml = `${inputHtml}
      style="width: ${width}"
    `;
  }

  inputHtml = `${inputHtml}
    >
      <span class="bringBooking_inputLabel"> ${label} </span>
      <input 
        class="bringBooking_inputBox ${clsName}" 
        placeholder="${placeholder}"
  `;

  if(value) {
    inputHtml = `${inputHtml}
      value="${value}"
    `;
  }

  inputHtml = `${inputHtml}
      />
      </div>
  `;

  return inputHtml;
}

// modal ui
const modalUI = html => {

  const backdropDiv = document.getElementById(backdropID);

  backdropDiv.style.display = 'flex';

  backdropDiv.innerHTML = `
    <div class="bringBooking_create_modal">

      <span 
      id="bringBooking_modal_close"
      onclick="modal_close_handler()"
      > 
        X 
      </span>

      ${html}

    </div>
  `;

}

// spinner ui
const spinnerUI = () => {

  const html = 
  `<div class="spinner_content">
      <div></div><div></div><div>
      </div><div></div><div></div>
      <div></div><div></div><div></div>
    </div>
  `;

  const backdropDiv = document.getElementById(backdropID);

  backdropDiv.style.display = 'flex';

  backdropDiv.innerHTML = html;

}

const setDateInput = (time, input) => {

  let { year, month, day } = time;


  if(`${day}`.length === 1) day = `0${day}`;
  if(`${month}`.length === 1) month = `0${month}`;


  input.value = `${day} - ${month} - ${year}`;

}

// booking options ui
const bookingOptionsUI = () => {


  // if(!Pikaday) return null;

  const html = `
    <div class="bringBooking_inputSection">

      <h3> Shipping Date </h3>

      <div class="bringBooking_inputContainer">

        ${inputBoxGenerator({ 
          label: 'Pick Date', clsName: 'bringBooking_input_datePick', placeholder: '16 - 02 - 2035',
          width: '150px',
        })}

        ${inputBoxGenerator({ 
          label: 'Hour', clsName: 'bringBooking_input_Hour', placeholder: '14', value: '14'
        })}

        ${inputBoxGenerator({ 
          label: 'Minute', clsName: 'bringBooking_input_Minute', placeholder: '00', value: '0'
        })}

      </div>

    </div>

    <div class="bringBooking_bookingConfirmBtn">
        <button 
          class='button button-primary'
          onclick="booking_create_hanlder()"
        > 
          Confirm 
        </button>
    </div>

  `;

  modalUI(html);

  const dateInput = document.querySelector('.bringBooking_input_datePick');

  // set default date for date picker
  const currentDate = new Date();

  input_shippingDate = {
    year: currentDate.getFullYear(),
    month: currentDate.getMonth() + 1,
    day: currentDate.getDate() + 1
  }

  setDateInput(input_shippingDate, dateInput);


  if(!Pikaday) return null;

  // handle pickaday
  new Pikaday({
    field: dateInput,
    onSelect: function(date) {
      
      let day = date.getDate();
      let month = date.getMonth()+1;
      const year = date.getFullYear();

      input_shippingDate = {
        year, month, day
      }

      setDateInput(input_shippingDate, dateInput);

      // if(`${day}`.length === 1) day = `0${day}`;
      // if(`${month}`.length === 1) month = `0${month}`;

      // dateInput.value = `${day} - ${month} - ${year}`;

    }
  });

}

// =============== booking create handler ====================
const booking_create_hanlder = async () => {
  try {

    if(!php_var_list) return null;

    // validate data
    if(!input_shippingDate.day || !input_shippingDate.month || !input_shippingDate.year) {
      throw new Error('Provide Shipping Date');
    }

    // validate minute
    let inputHour = document.querySelector('.bringBooking_input_Hour').value;
    
    if(!inputHour) {
      throw new Error('Provide Shipping Hour');
    }

    if(!isInteger(inputHour) || Number(inputHour) < 0 || Number(inputHour) > 24) {
      throw new Error('Invalid Shipping Hour');
    }


    // validate minute
    let inputMinute = document.querySelector('.bringBooking_input_Minute').value;

    
    if(!inputMinute) {
      throw new Error('Provide Shipping Minute');
    }

    if(!isInteger(inputMinute) || Number(inputMinute) < 0 || Number(inputMinute) > 60) {
      throw new Error('Invalid Shipping Minute');
    }

    const bookingData = {
      order_id: Number(order_id),
      shipping_time: {
        ...input_shippingDate,
        hour: inputHour,
        minute: inputMinute
      }
    }

    spinnerUI();

    const res = await fetch(
			`${php_var_list.site_url}/wp-json/bringBooking/create`,
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': php_var_list.nonce,
				},
				body: JSON.stringify(bookingData),
			}
		);


    if (!res.ok) {
			const errRes = await res.json();
			throw new Error(errRes.message);
		}

    const html = `
      <div class="booking_create_res_div">
        <h3> Booking Created </h3>
      </div>
    `;

    modalUI(html);

    await new Promise(resolve => setTimeout(resolve, 800));

    window.location.reload();

  }
  catch(err) {

    let errMsg = '';

    if(err.message) {
     errMsg = err.message;
    }

    const html = `
      <div class="booking_create_res_div">
        <h3> Failed to create booking </h3>
        <p> ${errMsg} </p>
      </div>
    `;

    modalUI(html);

  }
}


// =============== onload event listener ====================
window.addEventListener('load', () => {

  // get order button
  const orderBtn = document.getElementById('bringBooking_create_btn');

  if(!orderBtn) return null;

  const orderIdElement = orderBtn.getAttribute("orderid");

  if(!orderIdElement) return null;
  order_id = orderIdElement;

  const backdropDiv = document.createElement('div');
  backdropDiv.id = backdropID;
  document.body.appendChild(backdropDiv);

  orderBtn.addEventListener('click', bookingOptionsUI);
  // orderBtn.addEventListener('click', booking_create_hanlder);

});