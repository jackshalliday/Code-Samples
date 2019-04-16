using Graham.DataAccess.Model;
using Graham.Messages.Commands.Insights;
using Graham.Messages.Commands.InstagramAccount;
using Graham.Messages.Commands.Python;
using Graham.Messages.Events.InstagramAccount;
using Graham.Services;
using Graham.Static;
using NServiceBus;
using NServiceBus.Logging;
using System.Threading.Tasks;

namespace Graham.Backend.Handlers.InstagramAccount
{
    public class ValidateInstagramAccountHandler :
        IHandleMessages<ValidateInstagramAccount>,
        IHandleMessages<InstagramAccountLoginSuccessful>,
        IHandleMessages<InstagramAccountLoginFailed>
    {
        private static ILog log = LogManager.GetLogger<InstagramAccountAdded>();
        private readonly InstagramAccountService _service;

        public ValidateInstagramAccountHandler(InstagramAccountService service)
        {
            _service = service;
        }

        public async Task Handle(ValidateInstagramAccount message, IMessageHandlerContext context)
        {
            log.Info($"Received ValidateInstagramAccountPassword, InstagramAccountId = {message.InstagramAccountId}");

            await context.Send(new ExecuteLoginScript
            {
                InstagramAccountId = message.InstagramAccountId,
                Username = message.Username,
                Password = message.Password
            });
        }

        public async Task Handle(InstagramAccountLoginSuccessful message, IMessageHandlerContext context)
        {
            log.Info($"Received InstagramAccountLoginSuccessful, InstagramAccountId = {message.InstagramAccountId}");

            if (!_service.FindInstagramAccountById(message.InstagramAccountId, out var instagramAccount))
            {
                await context.Send(new SendErrorMessageToInsights
                {
                    ErrorMessage = "load this message from static class"
                });
            }

            instagramAccount.Validated = InstagramAccountValidationValue.Validated;
            _service.UpdateInstagramAccount(instagramAccount);
        }

        public async Task Handle(InstagramAccountLoginFailed message, IMessageHandlerContext context)
        {
            log.Info($"Received InstagramAccountLoginFailed, InstagramAccountId = {message.InstagramAccountId}");

            if (!_service.FindInstagramAccountById(message.InstagramAccountId, out var instagramAccount))
            {
                await context.Send(new SendErrorMessageToInsights
                {
                    ErrorMessage = "load this message from static class"
                });
            }

            instagramAccount.Validated = InstagramAccountValidationValue.NotValidated;
            _service.UpdateInstagramAccount(instagramAccount);
        }
    }
}
