using AutoMapper;
using Graham.DataAccess.Model;
using Graham.Messages.Commands.InstagramAccount;
using Graham.Messages.Events.InstagramAccount;
using Graham.Services.Interfaces;
using Graham.Static;
using Graham.WebAPI.DTOs;
using Microsoft.AspNetCore.Mvc;
using NServiceBus;
using System.Collections.Generic;
using System.Threading.Tasks;

namespace Graham.WebAPI.Controllers
{
    [Route("api/v1/[controller]")]
    [ApiController]
    public class InstagramAccountController : Controller
    {
        private readonly IInstagramAccountService _service;
        private readonly IMessageSession _messageSession;
        private readonly IMapper _mapper;

        public InstagramAccountController(IInstagramAccountService service, IMessageSession messageSession, IMapper mapper)
        {
            _service = service;
            _messageSession = messageSession;
            _mapper = mapper;
        }

        // GET api/intagramaccounts
        [HttpGet("Page/{page}")]
        public ActionResult<IEnumerable<InstagramAccount>> GetByPage(int page)
        {
            var instagramAccounts = _service.GetInstagramAccounts(page);
            return Ok(instagramAccounts);
        }

        // GET api/intagramaccounts/5
        [HttpGet("{id}")]
        public ActionResult<InstagramAccount> Get(int id)
        {
            if (!_service.FindInstagramAccountById(id, out var instagramAccount))
                return NotFound();

            return Ok(instagramAccount);
        }

        // POST api/intagramaccounts
        [HttpPost]
        public async Task<ActionResult> Post(InstagramAccountForCreationDto instagramAccountForCreation)
        {
            if (!ModelState.IsValid)
                return BadRequest();

            if (_service.FindInstagramAccountByUsername(instagramAccountForCreation.Username, out var instagramAccount))
                return Conflict();

            instagramAccount = _mapper.Map<InstagramAccount>(instagramAccountForCreation);

            _service.AddInstagramAccount(instagramAccount);

            await _messageSession.Publish(
                new InstagramAccountAdded
                {
                    InstagramAccountId = instagramAccount.Id,
                    Username = instagramAccount.Username,
                    Password = instagramAccount.Password
                });

            return Created($"api/v1/InstagramAccounts/{instagramAccount.Id}", instagramAccount);
        }

        // PUT api/intagramaccounts/5
        [HttpPut("{id}")]
        public async Task<ActionResult> Put(int id, [FromBody] InstagramAccountForUpdateDto instagramAccountForUpdate)
        {
            if (!ModelState.IsValid)
                return BadRequest();

            if (!_service.FindInstagramAccountById(id, out var instagramAccount))
                return NotFound();

            instagramAccount.Username = instagramAccountForUpdate.Username;
            instagramAccount.Password = instagramAccountForUpdate.Password;
            instagramAccount.Validated = InstagramAccountValidationValue.NotValidated;
            instagramAccount.ValidationInProgress = InstagramAccountValidationProgressValue.ValidationInProgress;

            _service.UpdateInstagramAccount(instagramAccount);

            await _messageSession.Send(new ValidateInstagramAccount
            {
                InstagramAccountId = instagramAccount.Id,
                Username = instagramAccount.Username,
                Password = instagramAccount.Password

            });

            return Accepted();
        }

        // DELETE api/intagramaccounts/5
        [HttpDelete("{id}")]
        public ActionResult Delete(int id)
        {
            if (!_service.FindInstagramAccountById(id, out var instagramAccount))
                return NotFound();

            _service.RemoveInstagramAccount(instagramAccount);

            return Accepted();
        }
    }
}
