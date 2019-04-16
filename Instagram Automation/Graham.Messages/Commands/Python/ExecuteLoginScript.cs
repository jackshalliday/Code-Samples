using NServiceBus;

namespace Graham.Messages.Commands.Python
{
    public class ExecuteLoginScript : ICommand
    {
        public long InstagramAccountId { get; set; }
        public string Username { get; set; }
        public string Password { get; set; }

    }
}
